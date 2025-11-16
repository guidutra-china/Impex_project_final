<?php

namespace App\Filament\Resources\SupplierQuotes\Products\Pages;

use App\Filament\Resources\SupplierQuotes\Products\ProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
