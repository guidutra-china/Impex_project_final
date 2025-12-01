<?php

namespace App\Filament\Resources\Shield\RoleResource\Pages;

use App\Filament\Resources\Shield\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['permissions'] = $this->record->permissions()->pluck('name')->toArray();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $permissions = $data['permissions'] ?? [];
        unset($data['permissions']);
        unset($data['select_all']);

        $this->permissions = $permissions;

        return $data;
    }

    protected function afterSave(): void
    {
        if (isset($this->permissions)) {
            $this->record->syncPermissions($this->permissions);
        }
    }
}
