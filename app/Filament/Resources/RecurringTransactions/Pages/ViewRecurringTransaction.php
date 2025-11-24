<?php

namespace App\Filament\Resources\RecurringTransactions\Pages;

use App\Filament\Resources\RecurringTransactions\RecurringTransactionResource;
use App\Models\FinancialTransaction;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewRecurringTransaction extends ViewRecord
{
    protected static string $resource = RecurringTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_now')
                ->label('Generate Transaction Now')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Generate Transaction Now')
                ->modalDescription('This will create a new financial transaction based on this recurrence and update the next due date.')
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
                            ->body("Transaction {$transaction->transaction_number} created successfully.")
                            ->success()
                            ->send();
                            
                        // Refresh to show updated next_due_date
                        $this->refreshFormData([
                            'next_due_date',
                        ]);
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

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Next Occurrences')
                    ->description('View the next 5 dates when this recurrence will generate transactions')
                    ->schema([
                        TextEntry::make('next_occurrences')
                            ->label('')
                            ->state(function ($record) {
                                if (!$record->is_active) {
                                    return 'Recurrence inactive - no transactions will be generated.';
                                }
                                
                                $occurrences = $record->getNextOccurrences(5);
                                
                                if (empty($occurrences)) {
                                    return 'No future occurrences found.';
                                }
                                
                                $list = [];
                                foreach ($occurrences as $index => $date) {
                                    $formatted = $date->format('Y-m-d');
                                    $list[] = ($index + 1) . ". {$formatted}";
                                }
                                
                                return implode("\n", $list);
                            })
                            ->html()
                            ->formatStateUsing(fn ($state) => nl2br(e($state)))
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(false),
            ]);
    }
}
