<?php

namespace App\Filament\Resources\DashboardConfigurations\Pages;

use App\Filament\Resources\DashboardConfigurations\DashboardConfigurationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDashboardConfiguration extends EditRecord
{
    protected static string $resource = DashboardConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
