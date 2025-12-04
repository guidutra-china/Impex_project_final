<?php

namespace App\Filament\Resources\DashboardConfigurationResource\Pages;

use App\Filament\Resources\DashboardConfigurationResource;
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
