<?php

namespace App\Filament\Resources\SupplierQuotes\QuotationOrders\Pages;

use App\Filament\Resources\SupplierQuotes\QuotationOrders\QuotationOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListQuotationOrders extends ListRecords
{
    protected static string $resource = QuotationOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
