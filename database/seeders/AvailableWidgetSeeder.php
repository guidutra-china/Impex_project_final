<?php

namespace Database\Seeders;

use App\Models\AvailableWidget;
use Illuminate\Database\Seeder;

class AvailableWidgetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $widgets = [
            [
                'widget_id' => 'calendar',
                'title' => 'Calendário',
                'description' => 'Visualize eventos e prazos importantes',
                'class' => 'App\Filament\Widgets\CalendarWidget',
                'icon' => 'heroicon-o-calendar',
                'category' => 'General',
                'is_available' => true,
                'default_visible' => true,
                'requires_permission' => null,
            ],
            [
                'widget_id' => 'rfq_stats',
                'title' => 'Estatísticas de RFQ',
                'description' => 'Acompanhe solicitações de cotação',
                'class' => 'App\Filament\Widgets\RfqStatsWidget',
                'icon' => 'heroicon-o-chart-bar',
                'category' => 'Sales',
                'is_available' => true,
                'default_visible' => true,
                'requires_permission' => null,
            ],
            [
                'widget_id' => 'purchase_order_stats',
                'title' => 'Estatísticas de Pedidos',
                'description' => 'Monitore seus pedidos de compra',
                'class' => 'App\Filament\Widgets\PurchaseOrderStatsWidget',
                'icon' => 'heroicon-o-shopping-cart',
                'category' => 'Purchasing',
                'is_available' => true,
                'default_visible' => true,
                'requires_permission' => null,
            ],
            [
                'widget_id' => 'financial_overview',
                'title' => 'Visão Financeira',
                'description' => 'Resumo financeiro do negócio',
                'class' => 'App\Filament\Widgets\FinancialOverviewWidget',
                'icon' => 'heroicon-o-banknotes',
                'category' => 'Finance',
                'is_available' => true,
                'default_visible' => true,
                'requires_permission' => null,
            ],
        ];

        foreach ($widgets as $widget) {
            AvailableWidget::updateOrCreate(
                ['widget_id' => $widget['widget_id']],
                $widget
            );
        }
    }
}
