<?php

namespace App\Filament\Resources\RecurringTransactions\Pages;

use App\Filament\Resources\RecurringTransactions\RecurringTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewRecurringTransaction extends ViewRecord
{
    protected static string $resource = RecurringTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('generate')
                ->label('Gerar Transação Agora')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    $transaction = $this->record->generateTransaction();
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Transação Gerada!')
                        ->body("Criada: {$transaction->transaction_number}")
                        ->success()
                        ->send();
                        
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                })
                ->visible(fn () => $this->record->is_active),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Próximas Ocorrências')
                    ->description('Preview das próximas 12 ocorrências desta transação recorrente')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('next_occurrences')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('date')
                                    ->label('Data')
                                    ->date('d/m/Y'),
                                Infolists\Components\TextEntry::make('amount')
                                    ->label('Valor')
                                    ->money($this->record->currency->code, divideBy: 100),
                            ])
                            ->columns(2)
                            ->state(fn () => $this->record->getNextOccurrences(12)),
                    ]),
            ]);
    }
}
