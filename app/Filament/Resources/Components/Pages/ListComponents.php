<?php

namespace App\Filament\Resources\SupplierQuotes\Components\Pages;

use App\Filament\Resources\SupplierQuotes\Components\ComponentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListComponents extends ListRecords
{
    protected static string $resource = ComponentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
