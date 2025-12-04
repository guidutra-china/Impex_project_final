<?php

namespace App\Filament\Resources\DashboardConfigurations\Pages;

use App\Filament\Resources\DashboardConfigurations\DashboardConfigurationResource;
use Filament\Resources\Pages\ListRecords;

class ListDashboardConfigurations extends ListRecords
{
    protected static string $resource = DashboardConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Não permitir criar novas configurações
        ];
    }
}
