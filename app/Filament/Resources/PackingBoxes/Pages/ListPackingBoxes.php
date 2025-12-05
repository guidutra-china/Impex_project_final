<?php

namespace App\Filament\Resources\PackingBoxes\Pages;

use App\Filament\Resources\PackingBoxes\PackingBoxResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPackingBoxes extends ListRecords
{
    protected static string $resource = PackingBoxResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action - boxes are created from Shipments
        ];
    }
}
