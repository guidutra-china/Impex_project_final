<?php

namespace App\Filament\Resources\RecurringTransactions\Pages;

use App\Filament\Resources\RecurringTransactions\RecurringTransactionResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewRecurringTransaction extends ViewRecord
{
    protected static string $resource = RecurringTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_now')
                ->label('Generate Next Transaction')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Generate Next Transaction')
                ->modalDescription('This will create the NEXT financial transaction based on this recurrence and update the next due date. Only ONE transaction will be created.')
                ->action(function () {
                    $recurring = $this->record;
                    
                    if (!$recurring->is_active) {
                        Notification::make()
                            ->title('Inactive Recurrence')
                            ->body('This recurrence is inactive. Please activate it first.')
                            ->danger()
                            ->send();
                        return;
                    }

                    try {
                        $transaction = $recurring->generateTransaction();
                        
                        Notification::make()
                            ->title('Transaction Generated!')
                            ->body("Transaction {$transaction->transaction_number} created successfully. Next due date updated.")
                            ->success()
                            ->send();
                            
                        // Redirect to view to refresh data
                        return redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error Generating Transaction')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            EditAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Recurrence Details')
                    ->schema([
                        Placeholder::make('name')
                            ->label(__('fields.name'))
                            ->content(fn ($record) => $record->name),
                        
                        Placeholder::make('type')
                            ->label(__('fields.type'))
                            ->content(fn ($record) => match($record->type) {
                                'payable' => 'Payable',
                                'receivable' => 'Receivable',
                                default => $record->type
                            }),
                        
                        Placeholder::make('amount')
                            ->label(__('fields.amount'))
                            ->content(fn ($record) => money($record->amount, $record->currency->code)),
                        
                        Placeholder::make('frequency')
                            ->label('Frequency')
                            ->content(fn ($record) => match($record->frequency) {
                                'daily' => 'Daily',
                                'weekly' => 'Weekly',
                                'monthly' => 'Monthly',
                                'quarterly' => 'Quarterly',
                                'yearly' => 'Yearly',
                                default => $record->frequency
                            }),
                        
                        Placeholder::make('next_due_date')
                            ->label('Next Due Date')
                            ->content(fn ($record) => $record->next_due_date->format('Y-m-d')),
                        
                        Placeholder::make('is_active')
                            ->label(__('fields.status'))
                            ->content(fn ($record) => $record->is_active ? '✅ Active' : '❌ Inactive'),
                    ])
                    ->columns(2),
                
                Section::make('Next 5 Occurrences')
                    ->description('Preview of the next 5 dates when this recurrence will generate transactions')
                    ->schema([
                        Placeholder::make('next_occurrences')
                            ->label('')
                            ->content(function ($record) {
                                if (!$record->is_active) {
                                    return '⚠️ Recurrence is inactive - no transactions will be generated automatically.';
                                }
                                
                                $occurrences = $record->getNextOccurrences(5);
                                
                                if (empty($occurrences)) {
                                    return '⚠️ No future occurrences found.';
                                }
                                
                                $html = '<div style="font-family: monospace;">';
                                foreach ($occurrences as $index => $occurrence) {
                                    $number = $index + 1;
                                    $date = $occurrence['date'];
                                    $amount = money($occurrence['amount'], $record->currency->code);
                                    $html .= "<div style='padding: 8px 0; border-bottom: 1px solid #e5e7eb;'>";
                                    $html .= "<strong>{$number}.</strong> {$date} - {$amount}";
                                    $html .= "</div>";
                                }
                                $html .= '</div>';
                                
                                return new \Illuminate\Support\HtmlString($html);
                            })
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(false),
            ]);
    }
}
