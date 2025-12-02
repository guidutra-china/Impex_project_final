<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CalendarWidget;
use App\Filament\Widgets\RfqStatsWidget;
use App\Filament\Widgets\PurchaseOrderStatsWidget;
use App\Filament\Widgets\FinancialOverviewWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use BackedEnum;

class Dashboard extends BaseDashboard
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-home';

    protected string $view = 'filament.pages.dashboard';

    /**
     * Get the widgets that should be displayed on the dashboard.
     */
    public function getWidgets(): array
    {
        return [
            CalendarWidget::class,
            RfqStatsWidget::class,
            PurchaseOrderStatsWidget::class,
            FinancialOverviewWidget::class,
        ];
    }

    /**
     * Get the columns for the dashboard layout.
     * You can customize the grid layout here.
     */
    public function getColumns(): int | string | array
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

    /**
     * Get the visible widgets.
     * This allows you to control which widgets are visible based on permissions.
     */
    public function getVisibleWidgets(): array
    {
        return $this->filterVisibleWidgets($this->getWidgets());
    }
}
