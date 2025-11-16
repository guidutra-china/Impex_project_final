<?php

namespace App\Filament\Resources\SupplierQuotes\Components\Pages;

use App\Filament\Resources\SupplierQuotes\Components\ComponentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditComponent extends EditRecord
{
    protected static string $resource = ComponentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
