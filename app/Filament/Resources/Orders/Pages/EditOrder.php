<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\FinancialCategory;
use App\Repositories\OrderRepository;
use App\Repositories\FinancialTransactionRepository;
use App\Services\RFQExcelService;
use App\Services\CustomerQuoteService;
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

    protected function getOrderRepository(): OrderRepository
    {
        return app(OrderRepository::class);
    }

    protected function getFinancialTransactionRepository(): FinancialTransactionRepository
    {
        return app(FinancialTransactionRepository::class);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_customer_quote')
                ->label('Generate Customer Quote')
                ->icon('heroicon-o-document-text')
                ->color('success')
                ->visible(fn() => $this->record->supplierQuotes()->where('status', '!=', 'draft')->count() > 0)
                ->form([
                    \Filament\Forms\Components\CheckboxList::make('supplier_quote_ids')
                        ->label('Select Supplier Quotes to Include')
                        ->options(function () {
                            return $this->record->supplierQuotes()
                                ->where('status', '!=', 'draft')
                                ->with('supplier')
                                ->get()
                                ->mapWithKeys(function ($quote) {
                                    $price = number_format($quote->total_price_after_commission / 100, 2);
                                    $currency = $quote->currency->code ?? 'USD';
                                    return [
                                        $quote->id => "{$quote->supplier->name} - {$currency} {$price} ({$quote->quote_number})"
                                    ];
                                });
                        })
                        ->required()
                        ->minItems(1)
                        ->helperText('Select at least one supplier quote to include in the customer quote'),
                    
                    TextInput::make('expiry_days')
                        ->label('Validity Period (Days)')
                        ->numeric()
                        ->default(7)
                        ->required()
                        ->minValue(1)
                        ->maxValue(90)
                        ->helperText('Number of days before the quote expires'),
                    
                    Textarea::make('internal_notes')
                        ->label('Internal Notes')
                        ->rows(3)
                        ->maxLength(500)
                        ->helperText('Notes for internal use (not visible to customer)'),
                ])
                ->action(function (array $data) {
                    $this->handleGenerateCustomerQuote($data);
                }),
            
            Action::make('add_project_expense')
                ->label('Add Project Expense')
                ->icon('heroicon-o-plus-circle')
                ->color('warning')
                ->form([
                    Select::make('financial_category_id')
                        ->label(__('fields.category'))
                        ->options(
                            FinancialCategory::where('code', 'LIKE', 'RFQ-EXP-%')
                                ->orWhere('code', 'RFQ-EXPENSES')
                                ->pluck('name', 'id')
                        )
                        ->searchable()
                        ->required()
                        ->helperText('Select the type of expense for this RFQ'),
                    
                    Select::make('currency_id')
                        ->label(__('fields.currency'))
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
                        ->label(__('fields.amount'))
                        ->numeric()
                        ->required()
                        ->minValue(0.01)
                        ->step(0.01)
                        ->helperText('Enter amount in the selected currency'),
                    
                    DatePicker::make('transaction_date')
                        ->label(__('fields.date'))
                        ->default(now())
                        ->required()
                        ->maxDate(now())
                        ->helperText('Date when the expense occurred'),
                    
                    DatePicker::make('due_date')
                        ->label(__('fields.due_date'))
                        ->default(now()->addDays(30))
                        ->required()
                        ->minDate(now())
                        ->helperText('Payment due date'),
                    
                    Textarea::make('description')
                        ->label(__('fields.description'))
                        ->required()
                        ->rows(3)
                        ->maxLength(500)
                        ->helperText('Describe the expense in detail'),
                    
                    Textarea::make('notes')
                        ->label(__('fields.notes'))
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
            $this->getFinancialTransactionRepository()->create([
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

    /**
     * Handle customer quote generation
     * 
     * @param array $data Form data
     */
    protected function handleGenerateCustomerQuote(array $data): void
    {
        $order = $this->record;

        try {
            $service = app(CustomerQuoteService::class);
            
            $customerQuote = $service->generate(
                $order,
                $data['supplier_quote_ids'],
                [
                    'expiry_days' => $data['expiry_days'] ?? 7,
                    'internal_notes' => $data['internal_notes'] ?? null,
                ]
            );

            Notification::make()
                ->title('Customer Quote Generated')
                ->success()
                ->body("Quote {$customerQuote->quote_number} has been created with {$customerQuote->items->count()} options.")
                ->send();

            // Redirect to the customer quote view (will be implemented in Phase 3)
            // For now, just refresh the page
            $this->redirect($this->getResource()::getUrl('edit', ['record' => $order->id]));
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Generating Quote')
                ->danger()
                ->body($e->getMessage())
                ->send();

            \Log::error('Error generating customer quote', [
                'order_id' => $order->id,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
