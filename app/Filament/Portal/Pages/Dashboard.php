<?php

namespace App\Filament\Portal\Pages;

use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-home';

    public static function getNavigationLabel(): string
    {
        return 'Dashboard';
    }

    public function getTitle(): string
    {
        return 'Customer Portal Dashboard';
    }

    protected string $view = 'filament.portal.pages.dashboard';

    /**
     * Allow portal users to access the dashboard.
     */
    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->client_id !== null;
    }

    /**
     * Get the widgets that should be displayed on the dashboard.
     */
    public function getWidgets(): array
    {
        $user = auth()->user();
        
        if (!$user) {
            return [];
        }

        // Return role-based widgets
        $widgets = [];

        if ($user->hasRole('purchasing')) {
            // Add purchasing widgets here in the future
        }

        if ($user->hasRole('finance')) {
            // Add finance widgets here in the future
        }

        return $widgets;
    }

    /**
     * Get the columns for the dashboard layout.
     */
    public function getColumns(): int | array
    {
        return [
            'default' => 1,
            'sm' => 2,
            'md' => 3,
            'lg' => 3,
            'xl' => 3,
            '2xl' => 3,
        ];
    }
}
