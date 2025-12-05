<?php

namespace App\Filament\Resources\ContainerTypes\Pages;

use App\Filament\Resources\ContainerTypes\ContainerTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContainerType extends CreateRecord
{
    protected static string $resource = ContainerTypeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        
        // Convert base_cost to cents if provided
        if (isset($data['base_cost']) && $data['base_cost'] > 0) {
            $data['base_cost'] = (int) ($data['base_cost'] * 100);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
