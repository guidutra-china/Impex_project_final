<?php

namespace App\Filament\Resources\SupplierQuotes\Pages;
use App\Filament\Resources\SupplierQuotes\SupplierQuoteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSupplierQuotes extends ListRecords
{
    protected static string $resource = SupplierQuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
