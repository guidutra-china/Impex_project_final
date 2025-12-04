<?php

namespace App\Filament\Resources\ShipmentContainers\Pages;

use App\Filament\Resources\ShipmentContainers\ShipmentContainerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditShipmentContainer extends EditRecord
{
    protected static string $resource = ShipmentContainerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
