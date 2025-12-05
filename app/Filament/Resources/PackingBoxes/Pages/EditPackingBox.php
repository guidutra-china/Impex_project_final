<?php

namespace App\Filament\Resources\PackingBoxes\Pages;

use App\Filament\Resources\PackingBoxes\PackingBoxResource;
use Filament\Resources\Pages\EditRecord;

class EditPackingBox extends EditRecord
{
    protected static string $resource = PackingBoxResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }

    public function getTitle(): string
    {
        return 'Manage Box #' . $this->record->box_number . ' Items';
    }
}
