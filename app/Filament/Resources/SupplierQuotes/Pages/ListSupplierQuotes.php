<?php

namespace App\Filament\Resources\SupplierQuotes\SupplierQuotes\Pages;
use App\Filament\Resources\SupplierQuotes\SupplierQuoteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSupplierQuotes extends ListRecords
{
    protected static string $resource = SupplierQuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
