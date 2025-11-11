<?php

namespace App\Filament\Resources\SupplierContacts\Pages;

use App\Filament\Resources\SupplierContacts\SupplierContactResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSupplierContacts extends ListRecords
{
    protected static string $resource = SupplierContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
