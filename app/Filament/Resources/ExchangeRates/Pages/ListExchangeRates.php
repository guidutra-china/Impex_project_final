<?php

namespace App\Filament\Resources\SupplierQuotes\ExchangeRates\Pages;

use App\Filament\Resources\SupplierQuotes\ExchangeRates\ExchangeRateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExchangeRates extends ListRecords
{
    protected static string $resource = ExchangeRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
