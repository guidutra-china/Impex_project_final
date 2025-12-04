<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CalendarWidget;
use App\Filament\Widgets\RfqStatsWidget;
use App\Filament\Widgets\PurchaseOrderStatsWidget;
use App\Filament\Widgets\FinancialOverviewWidget;
use App\Services\DashboardConfigurationService;
use Filament\Pages\Dashboard as BaseDashboard;
use BackedEnum;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-home';

    protected string $view = 'filament.pages.dashboard';

    /**
     * Mapeamento de IDs de widgets para suas classes
     */
    protected array $widgetClassMap = [
        'calendar' => CalendarWidget::class,
        'rfq_stats' => RfqStatsWidget::class,
        'purchase_order_stats' => PurchaseOrderStatsWidget::class,
        'financial_overview' => FinancialOverviewWidget::class,
    ];

    /**
     * Allow all authenticated users to access the dashboard.
     */
    public static function canAccess(): bool
    {
        return auth()->check();
    }

    /**
     * Get the widgets that should be displayed on the dashboard.
     * Carrega widgets baseado na configuração do usuário.
     */
    public function getWidgets(): array
    {
        $user = Auth::user();

        if (!$user) {
            return $this->getDefaultWidgets();
        }

        try {
            $dashboardService = app(DashboardConfigurationService::class);
            $config = $dashboardService->getUserConfiguration($user);

            if (!$config || empty($config->visible_widgets)) {
                return $this->getDefaultWidgets();
            }

            $visibleWidgets = $config->visible_widgets;
            $widgetOrder = $config->widget_order ?? [];

            // Construir array de widgets na ordem especificada
            $widgets = [];

            // Primeiro adicionar widgets na ordem especificada
            foreach ($widgetOrder as $widgetId) {
                if (in_array($widgetId, $visibleWidgets) && isset($this->widgetClassMap[$widgetId])) {
                    $widgets[] = $this->widgetClassMap[$widgetId];
                }
            }

            // Depois adicionar widgets visíveis que não estão na ordem
            foreach ($visibleWidgets as $widgetId) {
                if (!in_array($widgetId, $widgetOrder) && isset($this->widgetClassMap[$widgetId])) {
                    $widgets[] = $this->widgetClassMap[$widgetId];
                }
            }

            return !empty($widgets) ? $widgets : $this->getDefaultWidgets();
        } catch (\Exception $e) {
            \Log::error('Erro ao carregar widgets do dashboard: ' . $e->getMessage());

            return $this->getDefaultWidgets();
        }
    }

    /**
     * Retorna os widgets padrão
     */
    protected function getDefaultWidgets(): array
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

    /**
     * Get the visible widgets.
     * This allows you to control which widgets are visible based on permissions.
     */
    public function getVisibleWidgets(): array
    {
        return $this->filterVisibleWidgets($this->getWidgets());
    }
}
