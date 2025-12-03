<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\FinancialCategory;
use App\Models\FinancialTransaction;
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
                    
                    TextInput::make('amount')
                        ->label('Amount')
                        ->numeric()
                        ->prefix('$')
                        ->required()
                        ->minValue(0.01)
                        ->step(0.01)
                        ->helperText('Enter amount in dollars'),
                    
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
                    $order = $this->record;
                    
                    try {
                        FinancialTransaction::create([
                            'project_id' => $order->id,
                            'transactable_id' => $order->id,
                            'transactable_type' => get_class($order),
                            'type' => 'payable',
                            'status' => 'pending',
                            'financial_category_id' => $data['financial_category_id'],
                            'amount' => (int)($data['amount'] * 100), // Convert to cents
                            'paid_amount' => 0,
                            'currency_id' => $order->currency_id,
                            'exchange_rate_to_base' => 1.0, // TODO: Get actual exchange rate
                            'amount_base_currency' => (int)($data['amount'] * 100),
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
                    }
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
}
