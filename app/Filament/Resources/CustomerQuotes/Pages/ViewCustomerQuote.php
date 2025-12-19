<?php

namespace App\Filament\Resources\CustomerQuotes\Pages;

use App\Filament\Resources\CustomerQuotes\CustomerQuoteResource;
use App\Filament\Resources\CustomerQuotes\Schemas\CustomerQuoteInfolist;
use App\Mail\CustomerQuoteSent;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Mail;

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
                ->modalHeading('Send Quote to Customer')
                ->modalDescription('This will send an email with a link to view and select quote options.')
                ->form([
                    \Filament\Forms\Components\TextInput::make('email')
                        ->label('Customer Email')
                        ->email()
                        ->default(fn () => $this->record->order->customer->email)
                        ->required()
                        ->helperText('Email will be sent to this address'),
                ])
                ->action(function (array $data) {
                    try {
                        // Send email
                        Mail::to($data['email'])->send(new CustomerQuoteSent($this->record));
                        
                        // Update quote status
                        $this->record->update([
                            'status' => 'sent',
                            'sent_at' => now(),
                        ]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Quote Sent Successfully')
                            ->success()
                            ->body('Email sent to ' . $data['email'])
                            ->send();
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Error Sending Email')
                            ->danger()
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
            
            Action::make('reopen_quote')
                ->label('Reopen Quote')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn () => $this->record->status === 'accepted')
                ->requiresConfirmation()
                ->modalHeading('Reopen Quote for New Selection')
                ->modalDescription('This will allow the customer to make a new product selection. A new Proforma Invoice revision will be created when they submit.')
                ->action(function () {
                    $this->record->update([
                        'status' => 'pending',
                        'approved_at' => null,
                    ]);
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Quote Reopened')
                        ->success()
                        ->body('Customer can now make a new selection.')
                        ->send();
                }),
            
            Action::make('copy_public_link')
                ->label('Copy Public Link')
                ->icon('heroicon-o-link')
                ->action(function () {
                    $url = route('public.customer-quote.show', ['token' => $this->record->public_token]);
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
