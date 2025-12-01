<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\SupplierQuote;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class RfqStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    
    public static function canView(): bool
    {
        return auth()->user()->can('widget_RfqStatsWidget');
    }
    
    protected function getStats(): array
    {
        $user = auth()->user();
        
        // Check if user can see all clients
        $canSeeAll = $user->roles()->where('can_see_all', true)->exists();
        
        // Base query respects ClientOwnershipScope automatically
        $query = Order::query();
        
        // Total RFQs
        $totalRfqs = (clone $query)->count();
        
        // Active RFQs (draft, pending, sent, quoted)
        $activeRfqs = (clone $query)
            ->whereIn('status', ['draft', 'pending', 'sent', 'quoted'])
            ->count();
        
        // RFQs won (converted to orders)
        $wonRfqs = (clone $query)
            ->where('status', 'won')
            ->count();
        
        // Conversion rate
        $conversionRate = $totalRfqs > 0 ? round(($wonRfqs / $totalRfqs) * 100, 1) : 0;
        
        // Quotes received (respecting ownership)
        $quotesReceived = SupplierQuote::query()
            ->where('status', 'sent')
            ->count();
        
        // Average response time
        $avgResponseTime = SupplierQuote::where('status', 'sent')
            ->whereNotNull('created_at')
            ->selectRaw('AVG(DATEDIFF(updated_at, created_at)) as avg_days')
            ->value('avg_days');
        
        $avgResponseTime = $avgResponseTime ? round($avgResponseTime, 1) : 0;
        
        // RFQs created this month
        $thisMonthRfqs = (clone $query)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();
        
        // RFQs created last month
        $lastMonthRfqs = (clone $query)
            ->whereYear('created_at', now()->subMonth()->year)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->count();
        
        // Calculate trend
        $trend = $lastMonthRfqs > 0 
            ? round((($thisMonthRfqs - $lastMonthRfqs) / $lastMonthRfqs) * 100, 1)
            : 0;
        
        $scopeDescription = $canSeeAll ? 'Todas as RFQs' : 'Seus clientes';
        
        return [
            Stat::make('RFQs Ativas', $activeRfqs)
                ->description('Draft, Pendente, Enviadas, Cotadas')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('info')
                ->chart($this->getLastSevenDaysChart()),
            
            Stat::make('Cotações Recebidas', $quotesReceived)
                ->description("Tempo médio: {$avgResponseTime} dias")
                ->descriptionIcon('heroicon-o-inbox-arrow-down')
                ->color($avgResponseTime > 3 ? 'warning' : 'success'),
            
            Stat::make('Taxa de Conversão', "{$conversionRate}%")
                ->description("{$wonRfqs} RFQs ganhas de {$totalRfqs} total")
                ->descriptionIcon('heroicon-o-check-circle')
                ->color($conversionRate >= 30 ? 'success' : ($conversionRate >= 15 ? 'warning' : 'danger')),
            
            Stat::make('RFQs Este Mês', $thisMonthRfqs)
                ->description($trend >= 0 ? "+{$trend}% vs mês anterior" : "{$trend}% vs mês anterior")
                ->descriptionIcon($trend >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                ->color($trend >= 0 ? 'success' : 'danger'),
        ];
    }
    
    /**
     * Get chart data for last 7 days
     */
    protected function getLastSevenDaysChart(): array
    {
        $data = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $count = Order::whereDate('created_at', $date)->count();
            $data[] = $count;
        }
        
        return $data;
    }
}
