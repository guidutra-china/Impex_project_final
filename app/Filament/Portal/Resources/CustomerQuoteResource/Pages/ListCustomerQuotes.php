<?php

namespace App\Filament\Portal\Resources\CustomerQuoteResource\Pages;

use App\Filament\Portal\Resources\CustomerQuoteResource;
use Filament\Resources\Pages\ListRecords;

class ListCustomerQuotes extends ListRecords
{
    protected static string $resource = CustomerQuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action - quotes are created by admin
        ];
    }
}
