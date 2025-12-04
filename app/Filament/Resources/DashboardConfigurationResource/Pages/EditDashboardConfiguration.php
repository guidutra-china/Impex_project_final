<?php

namespace App\Filament\Resources\DashboardConfigurationResource\Pages;

use App\Filament\Resources\DashboardConfigurationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDashboardConfiguration extends EditRecord
{
    protected static string $resource = DashboardConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
