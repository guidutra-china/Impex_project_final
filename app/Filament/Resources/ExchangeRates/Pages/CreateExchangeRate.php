<?php

namespace App\Filament\Resources\SupplierQuotes\ExchangeRates\Pages;

use App\Filament\Resources\SupplierQuotes\ExchangeRates\ExchangeRateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateExchangeRate extends CreateRecord
{
    protected static string $resource = ExchangeRateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        
        return $data;
    }
}
