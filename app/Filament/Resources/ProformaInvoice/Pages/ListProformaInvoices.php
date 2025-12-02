<?php

namespace App\Filament\Resources\ProformaInvoice\Pages;

use App\Filament\Resources\ProformaInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProformaInvoices extends ListRecords
{
    protected static string $resource = ProformaInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
