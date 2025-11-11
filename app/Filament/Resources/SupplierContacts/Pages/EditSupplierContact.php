<?php

namespace App\Filament\Resources\SupplierContacts\Pages;

use App\Filament\Resources\SupplierContacts\SupplierContactResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSupplierContact extends EditRecord
{
    protected static string $resource = SupplierContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
