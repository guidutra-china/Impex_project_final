<?php

namespace App\Filament\Resources\PackingBoxTypes\Pages;

use App\Filament\Resources\PackingBoxTypes\PackingBoxTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditPackingBoxType extends EditRecord
{
    protected static string $resource = PackingBoxTypeResource::class;

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
        // Convert unit_cost from cents to dollars for display
        if (isset($data['unit_cost']) && $data['unit_cost'] > 0) {
            $data['unit_cost'] = $data['unit_cost'] / 100;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Convert unit_cost back to cents for storage
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
