<?php

namespace App\Filament\Widgets;

use App\Repositories\PurchaseOrderRepository;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PurchaseOrderStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected ?PurchaseOrderRepository $repository = null;

    public function mount(): void
    {
        $this->repository = app(PurchaseOrderRepository::class);
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
        return auth()->user()->can('View:PurchaseOrderStatsWidget');
    }
    
    protected function getStats(): array
    {
        // Ensure repository is initialized
        if (!$this->repository) {
            $this->repository = app(PurchaseOrderRepository::class);
        }
        
        $user = auth()->user();
        
        // Check if user can see all clients
        $canSeeAll = $user->roles()->where('can_see_all', true)->exists();
        
        // Count by status
        $pendingPOs = $this->repository->countByStatus('draft') + 
                      $this->repository->countByStatus('pending_approval');
        
        // Active POs
        $activePOs = $this->repository->countActive();
        
        // In Production
        $inProductionPOs = $this->repository->countByStatus('in_production');
        
        // Completed
        $completedPOs = $this->repository->countByStatus('completed');
        
        // Cancelled
        $cancelledPOs = $this->repository->countByStatus('cancelled');
        
        // Overdue POs (expected_delivery_date passed and not received)
        $overduePOs = $this->repository->getModel()
            ->where('expected_delivery_date', '<', now())
            ->whereNull('actual_delivery_date')
            ->whereIn('status', ['sent', 'confirmed', 'in_production'])
            ->count();
        
        // Total value of active POs (in base currency)
        $totalValueActive = $this->repository->getTotalActive() / 100; // Convert from cents
        
        // Format currency
        $formattedValue = 'R$ ' . number_format($totalValueActive, 2, ',', '.');
        
        // POs created this month
        $thisMonthPOs = $this->repository->getModel()
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();
        
        $scopeDescription = $canSeeAll ? 'All POs' : 'Your clients';
        
        return [
            Stat::make('Pending POs', $pendingPOs)
                ->description('Draft + Awaiting Approval')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),
            
            Stat::make('Active POs', $activePOs)
                ->description('Approved, Sent, Confirmed')
                ->descriptionIcon('heroicon-o-arrow-path')
                ->color('info')
                ->chart($this->getLastSevenDaysChart()),
            
            Stat::make('In Production', $inProductionPOs)
                ->description('Products being manufactured')
                ->descriptionIcon('heroicon-o-wrench-screwdriver')
                ->color('primary'),
            
            Stat::make('Overdue POs', $overduePOs)
                ->description('Delivery date passed')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($overduePOs > 0 ? 'danger' : 'success'),
            
            Stat::make('Open Value', $formattedValue)
                ->description('Active POs (base currency)')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('success'),
            
            Stat::make('POs This Month', $thisMonthPOs)
                ->description($scopeDescription)
                ->descriptionIcon('heroicon-o-shopping-cart')
                ->color('gray'),
        ];
    }
    
    /**
     * Get chart data for last 7 days
     */
    protected function getLastSevenDaysChart(): array
    {
        // Ensure repository is initialized
        if (!$this->repository) {
            $this->repository = app(PurchaseOrderRepository::class);
        }
        
        $data = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $count = $this->repository->getModel()
                ->whereDate('created_at', $date)
                ->count();
            $data[] = $count;
        }
        
        return $data;
    }
}
