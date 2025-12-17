<?php

namespace App\Filament\Portal\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use BackedEnum;

class Dashboard extends BaseDashboard
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.portal.pages.dashboard';

    public function getWidgets(): array
    {
        return [
            // Add portal-specific widgets here
        ];
    }
}
