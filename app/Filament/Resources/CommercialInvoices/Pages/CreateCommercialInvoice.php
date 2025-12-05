<?php

namespace App\Filament\Resources\CommercialInvoices\Pages;

use App\Filament\Resources\CommercialInvoices\CommercialInvoiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCommercialInvoice extends CreateRecord
{
    protected static string $resource = CommercialInvoiceResource::class;

    protected function afterCreate(): void
    {
        // Calculate totals from shipment after creating
        $this->record->calculateTotals();
    }
}
