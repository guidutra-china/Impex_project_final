<?php

namespace App\Filament\Resources\SupplierQuotes\Products\Pages;

use App\Filament\Resources\SupplierQuotes\Products\ProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * Recalculate manufacturing cost after saving
     */
    protected function afterSave(): void
    {
        $this->record->calculateManufacturingCost();
    }
}
