<?php

namespace App\Filament\Resources\SupplierQuotes\SupplierQuotes\Pages;

use App\Filament\Resources\SupplierQuotes\SupplierQuoteResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSupplierQuote extends CreateRecord
{
    protected static string $resource = SupplierQuoteResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();
        
        return $data;
    }

    protected function afterCreate(): void
    {
        // Lock exchange rate and calculate commission after creation
        $this->record->lockExchangeRate();
        $this->record->calculateCommission();
    }
}
