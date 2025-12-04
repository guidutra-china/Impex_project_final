<?php

namespace App\Services;

use App\Models\AvailableWidget;
use App\Models\User;

class WidgetRegistryService
{
    /**
     * Registrar todos os widgets disponíveis
     */
    public function registerWidgets(): void
    {
        $widgets = [
            [
                'widget_id' => 'calendar',
                'title' => 'Calendário',
                'description' => 'Visualizar eventos e compromissos',
                'class' => 'App\Filament\Widgets\CalendarWidget',
                'icon' => 'heroicon-o-calendar',
                'category' => 'calendar',
                'is_available' => true,
                'default_visible' => true,
                'requires_permission' => null,
            ],
            [
                'widget_id' => 'rfq_stats',
                'title' => 'Estatísticas de RFQ',
                'description' => 'Visualizar estatísticas de solicitações de cotação',
                'class' => 'App\Filament\Widgets\RfqStatsWidget',
                'icon' => 'heroicon-o-chart-bar',
                'category' => 'stats',
                'is_available' => true,
                'default_visible' => true,
                'requires_permission' => 'view_rfqs',
            ],
            [
                'widget_id' => 'purchase_order_stats',
                'title' => 'Estatísticas de Pedidos',
                'description' => 'Visualizar estatísticas de pedidos de compra',
                'class' => 'App\Filament\Widgets\PurchaseOrderStatsWidget',
                'icon' => 'heroicon-o-shopping-cart',
                'category' => 'stats',
                'is_available' => true,
                'default_visible' => true,
                'requires_permission' => 'view_purchase_orders',
            ],
            [
                'widget_id' => 'financial_overview',
                'title' => 'Visão Geral Financeira',
                'description' => 'Visualizar dados financeiros e receitas',
                'class' => 'App\Filament\Widgets\FinancialOverviewWidget',
                'icon' => 'heroicon-o-currency-dollar',
                'category' => 'financial',
                'is_available' => true,
                'default_visible' => true,
                'requires_permission' => 'view_financial_data',
            ],
            [
                'widget_id' => 'project_expenses',
                'title' => 'Despesas de Projeto',
                'description' => 'Visualizar despesas por projeto',
                'class' => 'App\Filament\Widgets\ProjectExpensesWidget',
                'icon' => 'heroicon-o-banknotes',
                'category' => 'financial',
                'is_available' => true,
                'default_visible' => false,
                'requires_permission' => 'view_expenses',
            ],
            [
                'widget_id' => 'related_documents',
                'title' => 'Documentos Relacionados',
                'description' => 'Visualizar documentos recentes',
                'class' => 'App\Filament\Widgets\RelatedDocumentsWidget',
                'icon' => 'heroicon-o-document',
                'category' => 'documents',
                'is_available' => true,
                'default_visible' => false,
                'requires_permission' => 'view_documents',
            ],
            [
                'widget_id' => 'container_utilization',
                'title' => 'Utilização de Containers',
                'description' => 'Visualizar utilização de containers de shipment',
                'class' => 'App\Filament\Widgets\ContainerUtilizationWidget',
                'icon' => 'heroicon-o-cube-transparent',
                'category' => 'shipment',
                'is_available' => true,
                'default_visible' => false,
                'requires_permission' => 'view_shipments',
            ],
        ];

        foreach ($widgets as $widget) {
            AvailableWidget::updateOrCreate(
                ['widget_id' => $widget['widget_id']],
                $widget
            );
        }
    }

    /**
     * Obter widgets disponíveis para um usuário
     */
    public function getAvailableWidgetsForUser(User $user): array
    {
        $allWidgets = AvailableWidget::where('is_available', true)->get();
        $available = [];

        foreach ($allWidgets as $widget) {
            // Verificar se widget requer permissão
            if ($widget->requires_permission && !$user->can($widget->requires_permission)) {
                continue;
            }

            $available[] = [
                'id' => $widget->widget_id,
                'title' => $widget->title,
                'description' => $widget->description,
                'icon' => $widget->icon,
                'category' => $widget->category,
                'class' => $widget->class,
            ];
        }

        return $available;
    }

    /**
     * Obter widget por ID
     */
    public function getWidget(string $widgetId): ?array
    {
        $widget = AvailableWidget::where('widget_id', $widgetId)->first();

        if (!$widget) {
            return null;
        }

        return [
            'id' => $widget->widget_id,
            'title' => $widget->title,
            'description' => $widget->description,
            'icon' => $widget->icon,
            'category' => $widget->category,
            'class' => $widget->class,
        ];
    }

    /**
     * Obter widgets por categoria
     */
    public function getWidgetsByCategory(string $category): array
    {
        $widgets = AvailableWidget::where('category', $category)
            ->where('is_available', true)
            ->get();

        return $widgets->map(fn($w) => [
            'id' => $w->widget_id,
            'title' => $w->title,
            'description' => $w->description,
            'icon' => $w->icon,
            'category' => $w->category,
            'class' => $w->class,
        ])->toArray();
    }

    /**
     * Obter todas as categorias
     */
    public function getCategories(): array
    {
        return AvailableWidget::where('is_available', true)
            ->distinct()
            ->pluck('category')
            ->toArray();
    }

    /**
     * Verificar se widget existe
     */
    public function widgetExists(string $widgetId): bool
    {
        return AvailableWidget::where('widget_id', $widgetId)
            ->where('is_available', true)
            ->exists();
    }
}
