<?php

namespace App\Filament\Resources\CustomerQuotes\Pages;

use App\Filament\Resources\CustomerQuotes\CustomerQuoteResource;
use App\Filament\Resources\CustomerQuotes\Schemas\CustomerQuoteInfolist;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewCustomerQuote extends ViewRecord
{
    protected static string $resource = CustomerQuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('send_to_customer')
                ->label('Send to Customer')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->visible(fn () => $this->record->status === 'draft')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update([
                        'status' => 'sent',
                        'sent_at' => now(),
                    ]);
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Quote Sent')
                        ->success()
                        ->body('The quote has been marked as sent.')
                        ->send();
                }),
            
            Action::make('copy_public_link')
                ->label('Copy Public Link')
                ->icon('heroicon-o-link')
                ->action(function () {
                    $url = route('customer-quote.public', ['token' => $this->record->public_token]);
                    \Filament\Notifications\Notification::make()
                        ->title('Public Link')
                        ->body($url)
                        ->success()
                        ->send();
                }),
            
            EditAction::make()
                ->label('Edit')
                ->icon('heroicon-o-pencil-square'),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return CustomerQuoteInfolist::configure($schema);
    }
}
