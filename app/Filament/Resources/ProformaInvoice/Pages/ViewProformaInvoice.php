<?php

namespace App\Filament\Resources\ProformaInvoice\Pages;

use App\Filament\Resources\ProformaInvoice\ProformaInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewProformaInvoice extends ViewRecord
{
    protected static string $resource = ProformaInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
