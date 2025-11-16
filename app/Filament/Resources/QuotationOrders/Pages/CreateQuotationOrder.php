<?php

namespace App\Filament\Resources\SupplierQuotes\QuotationOrders\Pages;

use App\Filament\Resources\SupplierQuotes\QuotationOrders\QuotationOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuotationOrder extends CreateRecord
{
    protected static string $resource = QuotationOrderResource::class;
}
