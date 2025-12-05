<?php

namespace App\Filament\Resources\ContainerTypes\Pages;

use App\Filament\Resources\ContainerTypes\ContainerTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditContainerType extends EditRecord
{
    protected static string $resource = ContainerTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Convert base_cost from cents to dollars for display
        if (isset($data['base_cost']) && $data['base_cost'] > 0) {
            $data['base_cost'] = $data['base_cost'] / 100;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Convert base_cost back to cents for storage
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
