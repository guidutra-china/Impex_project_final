<?php

namespace App\Filament\Widgets;

use App\Repositories\RFQRepository;
use App\Repositories\SupplierQuoteRepository;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RfqStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected ?RFQRepository $rfqRepository = null;
    protected ?SupplierQuoteRepository $quoteRepository = null;

    public function mount(): void
    {
        $this->rfqRepository = app(RFQRepository::class);
        $this->quoteRepository = app(SupplierQuoteRepository::class);
    }
    
    public static function canView(): bool
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return false;
        }
        
        // Allow panel_user role to view dashboard
        if (auth()->user()->hasRole('panel_user')) {
            return true;
        }
        
        // Check permission using Shield's actual format (uses separator from config)
        return auth()->user()->can('View:RfqStatsWidget');
    }
    
    protected function getStats(): array
    {
        // Ensure repositories are initialized
        if (!$this->rfqRepository) {
            $this->rfqRepository = app(RFQRepository::class);
        }
        if (!$this->quoteRepository) {
            $this->quoteRepository = app(SupplierQuoteRepository::class);
        }
        
        $user = auth()->user();
        
        // Check if user can see all clients
        $canSeeAll = $user->roles()->where('can_see_all', true)->exists();
        
        // Total RFQs
        $totalRfqs = $this->rfqRepository->count();
        
        // Active RFQs (draft, pending, sent, quoted)
        $activeRfqs = $this->rfqRepository->countOpen();
        
        // RFQs won (converted to orders)
        $wonRfqs = $this->rfqRepository->countByStatus('approved');
        
        // Conversion rate
        $conversionRate = $totalRfqs > 0 ? round(($wonRfqs / $totalRfqs) * 100, 1) : 0;
        
        // Quotes received
        $quotesReceived = $this->quoteRepository->countByStatus('sent');
        
        // Average response time
        $avgResponseTime = $this->calculateAverageResponseTime();
        
        // RFQs created this month
        $thisMonthRfqs = $this->rfqRepository->getModel()
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();
        
        // RFQs created last month
        $lastMonthRfqs = $this->rfqRepository->getModel()
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
            $count = $this->rfqRepository->getModel()
                ->whereDate('created_at', $date)
                ->count();
            $data[] = $count;
        }
        
        return $data;
    }

    /**
     * Calcula o tempo médio de resposta de cotações
     */
    protected function calculateAverageResponseTime(): float
    {
        $avgResponseTime = $this->quoteRepository->getModel()
            ->where('status', 'sent')
            ->whereNotNull('created_at')
            ->selectRaw('AVG(DATEDIFF(updated_at, created_at)) as avg_days')
            ->value('avg_days');
        
        return $avgResponseTime ? round($avgResponseTime, 1) : 0;
    }
}
