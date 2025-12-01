<?php

namespace App\Filament\Resources\Shield\RoleResource\Pages;

use App\Filament\Resources\Shield\RoleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $permissions = $data['permissions'] ?? [];
        unset($data['permissions']);
        unset($data['select_all']);

        $this->permissions = $permissions;

        return $data;
    }

    protected function afterCreate(): void
    {
        if (isset($this->permissions) && count($this->permissions) > 0) {
            $this->record->givePermissionTo($this->permissions);
        }
    }
}
