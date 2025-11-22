<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\SupplierQuote;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RfqStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // Count open RFQs
        $openRfqs = Order::whereIn('status', ['draft', 'pending', 'sent'])->count();
        
        // Count quotes received
        $quotesReceived = SupplierQuote::where('status', 'sent')->count();
        
        // Count quotes pending response
        $quotesPending = SupplierQuote::where('status', 'draft')->count();
        
        // Calculate average response time (simplified)
        $avgResponseTime = SupplierQuote::where('status', 'sent')
            ->whereNotNull('created_at')
            ->selectRaw('AVG(DATEDIFF(updated_at, created_at)) as avg_days')
            ->value('avg_days');
        
        $avgResponseTime = $avgResponseTime ? round($avgResponseTime, 1) : 0;
        
        return [
            Stat::make('Open RFQs', $openRfqs)
                ->description('Active requests for quotation')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info')
                ->url(route('filament.admin.resources.orders.index', [
                    'tableFilters' => ['status' => ['values' => ['pending', 'sent']]]
                ])),
            
            Stat::make('Quotes Received', $quotesReceived)
                ->description('Awaiting review')
                ->descriptionIcon('heroicon-m-inbox-arrow-down')
                ->color('success')
                ->url(route('filament.admin.resources.supplier-quotes.index', [
                    'tableFilters' => ['status' => ['value' => 'sent']]
                ])),
            
            Stat::make('Avg Response Time', $avgResponseTime . ' days')
                ->description('Supplier response time')
                ->descriptionIcon('heroicon-m-clock')
                ->color($avgResponseTime > 3 ? 'warning' : 'success'),
        ];
    }
}
