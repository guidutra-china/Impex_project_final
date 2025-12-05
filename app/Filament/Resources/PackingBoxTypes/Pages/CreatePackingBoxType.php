<?php

namespace App\Filament\Resources\PackingBoxTypes\Pages;

use App\Filament\Resources\PackingBoxTypes\PackingBoxTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePackingBoxType extends CreateRecord
{
    protected static string $resource = PackingBoxTypeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        
        // Convert unit_cost to cents if provided
        if (isset($data['unit_cost']) && $data['unit_cost'] > 0) {
            $data['unit_cost'] = (int) ($data['unit_cost'] * 100);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
