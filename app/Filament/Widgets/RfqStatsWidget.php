<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\SupplierQuote;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class RfqStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    
    public static function canView(): bool
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return false;
        }
        
        // Check permission using Shield's actual format (uses separator from config)
        return auth()->user()->can('View:RfqStatsWidget');
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
        
        $scopeDescription = $canSeeAll ? 'All RFQs' : 'Your clients';
        
        return [
            Stat::make('Active RFQs', $activeRfqs)
                ->description('Draft, Pending, Sent, Quoted')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('info')
                ->chart($this->getLastSevenDaysChart()),
            
            Stat::make('Quotes Received', $quotesReceived)
                ->description("Avg time: {$avgResponseTime} days")
                ->descriptionIcon('heroicon-o-inbox-arrow-down')
                ->color($avgResponseTime > 3 ? 'warning' : 'success'),
            
            Stat::make('Conversion Rate', "{$conversionRate}%")
                ->description("{$wonRfqs} RFQs won out of {$totalRfqs} total")
                ->descriptionIcon('heroicon-o-check-circle')
                ->color($conversionRate >= 30 ? 'success' : ($conversionRate >= 15 ? 'warning' : 'danger')),
            
            Stat::make('RFQs This Month', $thisMonthRfqs)
                ->description($trend >= 0 ? "+{$trend}% vs last month" : "{$trend}% vs last month")
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
