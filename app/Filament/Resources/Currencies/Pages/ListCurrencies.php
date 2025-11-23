<?php

namespace App\Filament\Resources\Currencies\Pages;

use App\Filament\Resources\Currencies\CurrencyResource;
use App\Services\CurrencyExchangeService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Exception;

class ListCurrencies extends ListRecords
{
    protected static string $resource = CurrencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('update_rates')
                ->label('Update Exchange Rates')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Update Exchange Rates')
                ->modalDescription('This will fetch the latest exchange rates from ExchangeRate-API and update all active currencies based on your base currency.')
                ->modalSubmitActionLabel('Update Rates')
                ->action(function (CurrencyExchangeService $exchangeService) {
                    try {
                        $stats = $exchangeService->updateAllRates();

                        Notification::make()
                            ->title('Exchange rates updated successfully!')
                            ->body("Updated: {$stats['updated']} | Skipped: {$stats['skipped']} | Failed: {$stats['failed']}")
                            ->success()
                            ->duration(5000)
                            ->send();

                        // Refresh the page to show updated rates
                        $this->redirect(static::getUrl());
                    } catch (Exception $e) {
                        Notification::make()
                            ->title('Failed to update exchange rates')
                            ->body($e->getMessage())
                            ->danger()
                            ->duration(10000)
                            ->send();
                    }
                }),
            CreateAction::make(),
        ];
    }
}
