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
                ->label('Gerar Transação Agora')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Gerar Transação Agora')
                ->modalDescription('Isso criará uma nova transação financeira baseada nesta recorrência e atualizará a próxima data de vencimento.')
                ->action(function () {
                    $recurring = $this->record;
                    
                    if (!$recurring->is_active) {
                        Notification::make()
                            ->title('Recorrência Inativa')
                            ->body('Esta recorrência está inativa. Ative-a primeiro.')
                            ->danger()
                            ->send();
                        return;
                    }

                    try {
                        $transaction = $recurring->generateTransaction();
                        
                        Notification::make()
                            ->title('Transação Gerada!')
                            ->body("Transação {$transaction->transaction_number} criada com sucesso.")
                            ->success()
                            ->send();
                            
                        // Refresh to show updated next_due_date
                        $this->refreshFormData([
                            'next_due_date',
                        ]);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Erro ao Gerar Transação')
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
                Section::make('Próximas Ocorrências')
                    ->description('Visualize as próximas 5 datas em que esta recorrência gerará transações')
                    ->schema([
                        TextEntry::make('next_occurrences')
                            ->label('')
                            ->state(function ($record) {
                                if (!$record->is_active) {
                                    return 'Recorrência inativa - nenhuma transação será gerada.';
                                }
                                
                                $occurrences = $record->getNextOccurrences(5);
                                
                                if (empty($occurrences)) {
                                    return 'Nenhuma ocorrência futura encontrada.';
                                }
                                
                                $list = [];
                                foreach ($occurrences as $index => $date) {
                                    $formatted = $date->format('d/m/Y');
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
