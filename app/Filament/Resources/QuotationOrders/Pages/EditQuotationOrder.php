<?php

namespace App\Filament\Resources\SupplierQuotes\QuotationOrders\Pages;

use App\Filament\Resources\SupplierQuotes\QuotationOrders\QuotationOrderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditQuotationOrder extends EditRecord
{
    protected static string $resource = QuotationOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
