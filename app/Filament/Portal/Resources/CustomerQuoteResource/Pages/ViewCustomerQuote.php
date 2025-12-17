<?php

namespace App\Filament\Portal\Resources\CustomerQuoteResource\Pages;

use App\Filament\Portal\Resources\CustomerQuoteResource;
use App\Services\CustomerQuoteService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomerQuote extends ViewRecord
{
    protected static string $resource = CustomerQuoteResource::class;

    protected static string $view = 'filament.portal.pages.customer-quote-view';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('select_option')
                ->label('Select Option')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->status !== 'accepted')
                ->form([
                    \Filament\Forms\Components\Select::make('item_id')
                        ->label('Choose Your Preferred Option')
                        ->options(function () {
                            return $this->record->items->mapWithKeys(function ($item) {
                                return [
                                    $item->id => "{$item->display_name} - $" . number_format($item->price_after_commission / 100, 2) . 
                                        ($item->delivery_time ? " - Delivery: {$item->delivery_time}" : "")
                                ];
                            });
                        })
                        ->required()
                        ->helperText('Select the option that best meets your requirements'),
                ])
                ->action(function (array $data, CustomerQuoteService $service) {
                    try {
                        $service->approveItem($this->record, $data['item_id']);

                        Notification::make()
                            ->success()
                            ->title('Option Selected')
                            ->body('Your selection has been recorded. Our team will process your order.')
                            ->send();

                        return redirect()->to(static::getResource()::getUrl('view', ['record' => $this->record]));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
        ];
    }
}
