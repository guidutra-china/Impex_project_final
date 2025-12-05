<?php

namespace App\Filament\Resources\PackingBoxTypes\Pages;

use App\Filament\Resources\PackingBoxTypes\PackingBoxTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPackingBoxTypes extends ListRecords
{
    protected static string $resource = PackingBoxTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
