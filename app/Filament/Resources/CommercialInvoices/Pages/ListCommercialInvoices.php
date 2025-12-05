<?php

namespace App\Filament\Resources\CommercialInvoices\Pages;

use App\Filament\Resources\CommercialInvoices\CommercialInvoiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCommercialInvoices extends ListRecords
{
    protected static string $resource = CommercialInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
