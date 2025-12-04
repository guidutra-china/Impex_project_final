<?php

namespace App\Filament\Resources\ShipmentContainerResource\Pages;

use App\Filament\Resources\ShipmentContainerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditShipmentContainer extends EditRecord
{
    protected static string $resource = ShipmentContainerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
