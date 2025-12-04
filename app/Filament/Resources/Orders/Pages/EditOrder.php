<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\FinancialCategory;
use App\Repositories\OrderRepository;
use App\Repositories\FinancialTransactionRepository;
use App\Services\RFQExcelService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected ?OrderRepository $orderRepository = null;
    protected ?FinancialTransactionRepository $financialTransactionRepository = null;

    public function mount(): void
    {
        parent::mount();
        $this->orderRepository = app(OrderRepository::class);
        $this->financialTransactionRepository = app(FinancialTransactionRepository::class);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('add_project_expense')
                ->label('Add Project Expense')
                ->icon('heroicon-o-plus-circle')
                ->color('warning')
                ->form([
                    Select::make('financial_category_id')
                        ->label('Expense Category')
                        ->options(
                            FinancialCategory::where('code', 'LIKE', 'RFQ-EXP-%')
                                ->orWhere('code', 'RFQ-EXPENSES')
                                ->pluck('name', 'id')
                        )
                        ->searchable()
                        ->required()
                        ->helperText('Select the type of expense for this RFQ'),
                    
                    Select::make('currency_id')
                        ->label('Currency')
                        ->relationship('currency', 'code')
                        ->default(fn() => $this->record->currency_id)
                        ->required()
                        ->searchable()
                        ->preload()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (!$state) return;
                            
                            // Get base currency (USD)
                            $baseCurrency = \App\Models\Currency::where('is_base', true)->first();
                            if (!$baseCurrency) return;
                            
                            // If selected currency is base currency, rate is 1
                            if ($state == $baseCurrency->id) {
                                $set('exchange_rate', 1.0000);
                                return;
                            }
                            
                            // Get latest exchange rate
                            $rate = \App\Models\ExchangeRate::getConversionRate($state, $baseCurrency->id);
                            if ($rate) {
                                $set('exchange_rate', number_format($rate, 4, '.', ''));
                            }
                        })
                        ->helperText('Currency of the expense'),
                    
                    TextInput::make('exchange_rate')
                        ->label('Exchange Rate to USD')
                        ->numeric()
                        ->default(1.00)
                        ->required()
                        ->minValue(0.0001)
                        ->step(0.0001)
                        ->helperText('Automatically loaded from Exchange Rates (editable)'),
                    
                    TextInput::make('amount')
                        ->label('Amount')
                        ->numeric()
                        ->required()
                        ->minValue(0.01)
                        ->step(0.01)
                        ->helperText('Enter amount in the selected currency'),
                    
                    DatePicker::make('transaction_date')
                        ->label('Transaction Date')
                        ->default(now())
                        ->required()
                        ->maxDate(now())
                        ->helperText('Date when the expense occurred'),
                    
                    DatePicker::make('due_date')
                        ->label('Due Date')
                        ->default(now()->addDays(30))
                        ->required()
                        ->minDate(now())
                        ->helperText('Payment due date'),
                    
                    Textarea::make('description')
                        ->label('Description')
                        ->required()
                        ->rows(3)
                        ->maxLength(500)
                        ->helperText('Describe the expense in detail'),
                    
                    Textarea::make('notes')
                        ->label('Additional Notes')
                        ->rows(2)
                        ->maxLength(500)
                        ->helperText('Optional additional information'),
                ])
                ->action(function (array $data) {
                    $this->handleAddProjectExpense($data);
                }),
            
            DeleteAction::make(),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction(),
            Action::make('download_rfq_excel')
                ->label('Download RFQ Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $order = $this->record;
                    
                    // Generate Excel
                    $excelService = new RFQExcelService();
                    $filePath = $excelService->generateRFQ($order);
                    
                    // Stream the file
                    return response()->download($filePath, basename($filePath))->deleteFileAfterSend(true);
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Convert items requested_unit_price from cents to decimal
        if (isset($data['items'])) {
            foreach ($data['items'] as $key => $item) {
                if (isset($item['requested_unit_price'])) {
                    $data['items'][$key]['requested_unit_price'] = $item['requested_unit_price'] / 100;
                }
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();
        
        return $data;
    }

    protected function getFooterWidgets(): array
    {
        return [
            \App\Filament\Widgets\ProjectExpensesWidget::class,
            \App\Filament\Widgets\RelatedDocumentsWidget::class,
        ];
    }

    /**
     * Manipula a adição de despesa de projeto
     * 
     * @param array $data Dados da despesa
     */
    protected function handleAddProjectExpense(array $data): void
    {
        $order = $this->record;

        try {
            $amountInCents = (int)($data['amount'] * 100);
            $exchangeRate = $data['exchange_rate'] ?? 1.0;
            $amountInBaseCurrency = (int)($data['amount'] * $exchangeRate * 100);

            // Usar repository para criar transação
            $this->financialTransactionRepository->create([
                'project_id' => $order->id,
                'transactable_id' => $order->id,
                'transactable_type' => get_class($order),
                'type' => 'payable',
                'status' => 'pending',
                'financial_category_id' => $data['financial_category_id'],
                'amount' => $amountInCents,
                'paid_amount' => 0,
                'currency_id' => $data['currency_id'],
                'exchange_rate_to_base' => $exchangeRate,
                'amount_base_currency' => $amountInBaseCurrency,
                'transaction_date' => $data['transaction_date'],
                'due_date' => $data['due_date'],
                'description' => $data['description'],
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            Notification::make()
                ->title('Project expense added successfully')
                ->success()
                ->body('The expense has been linked to this RFQ.')
                ->send();

            // Refresh the page to show the new expense in the widget
            $this->redirect($this->getResource()::getUrl('edit', ['record' => $order->id]));
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error adding expense')
                ->danger()
                ->body($e->getMessage())
                ->send();

            \Log::error('Erro ao adicionar despesa de projeto', [
                'order_id' => $order->id,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
