<?php

namespace App\Filament\Resources\CustomerQuotes\Pages;

use App\Filament\Resources\CustomerQuotes\CustomerQuoteResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerQuote extends CreateRecord
{
    protected static string $resource = CustomerQuoteResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
