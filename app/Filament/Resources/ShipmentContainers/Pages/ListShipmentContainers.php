<?php

namespace App\Filament\Resources\ShipmentContainers\Pages;

use App\Filament\Resources\ShipmentContainers\ShipmentContainerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListShipmentContainers extends ListRecords
{
    protected static string $resource = ShipmentContainerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
