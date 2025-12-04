<?php

namespace App\Filament\Resources\ShipmentContainerResource\Pages;

use App\Filament\Resources\ShipmentContainerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateShipmentContainer extends CreateRecord
{
    protected static string $resource = ShipmentContainerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        return $data;
    }
}
