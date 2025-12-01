<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseOrder;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class PurchaseOrderStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    
    protected function getStats(): array
    {
        $user = auth()->user();
        
        // Check if user can see all clients
        $canSeeAll = $user->roles()->where('can_see_all', true)->exists();
        
        // Base query respects ClientOwnershipScope automatically
        $query = PurchaseOrder::query();
        
        // Count by status
        $statusCounts = (clone $query)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        
        // Draft + Pending Approval
        $pendingPOs = ($statusCounts['draft'] ?? 0) + ($statusCounts['pending_approval'] ?? 0);
        
        // Approved + Sent + Confirmed
        $activePOs = ($statusCounts['approved'] ?? 0) 
            + ($statusCounts['sent'] ?? 0) 
            + ($statusCounts['confirmed'] ?? 0)
            + ($statusCounts['partially_received'] ?? 0);
        
        // In Production
        $inProductionPOs = $statusCounts['in_production'] ?? 0;
        
        // Completed
        $completedPOs = $statusCounts['completed'] ?? 0;
        
        // Cancelled
        $cancelledPOs = $statusCounts['cancelled'] ?? 0;
        
        // Overdue POs (expected_delivery_date passed and not received)
        $overduePOs = (clone $query)
            ->where('expected_delivery_date', '<', now())
            ->whereNull('actual_delivery_date')
            ->whereIn('status', ['sent', 'confirmed', 'in_production'])
            ->count();
        
        // Total value of active POs (in base currency)
        $totalValueActive = (clone $query)
            ->whereIn('status', ['approved', 'sent', 'confirmed', 'in_production', 'partially_received'])
            ->sum(DB::raw('COALESCE(total_base_currency, 0)'));
        
        // Convert from cents to currency
        $totalValueActive = $totalValueActive / 100;
        
        // Format currency
        $formattedValue = 'R$ ' . number_format($totalValueActive, 2, ',', '.');
        
        // POs created this month
        $thisMonthPOs = (clone $query)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();
        
        $scopeDescription = $canSeeAll ? 'Todas as POs' : 'Seus clientes';
        
        return [
            Stat::make('POs Pendentes', $pendingPOs)
                ->description('Draft + Aguardando Aprovação')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),
                // ->url(route('filament.admin.resources.purchase-orders.purchase-orders.index'))
            
            Stat::make('POs Ativas', $activePOs)
                ->description('Aprovadas, Enviadas, Confirmadas')
                ->descriptionIcon('heroicon-o-arrow-path')
                ->color('info')
                ->chart($this->getLastSevenDaysChart()),
            
            Stat::make('Em Produção', $inProductionPOs)
                ->description('Produtos sendo fabricados')
                ->descriptionIcon('heroicon-o-wrench-screwdriver')
                ->color('primary'),
            
            Stat::make('POs Atrasadas', $overduePOs)
                ->description('Data de entrega passou')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($overduePOs > 0 ? 'danger' : 'success'),
                // ->url($overduePOs > 0 ? route('filament.admin.resources.purchase-orders.purchase-orders.index') : null)
            
            Stat::make('Valor em Aberto', $formattedValue)
                ->description('POs ativas (moeda base)')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('success'),
            
            Stat::make('POs Este Mês', $thisMonthPOs)
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
        $data = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $count = PurchaseOrder::whereDate('created_at', $date)->count();
            $data[] = $count;
        }
        
        return $data;
    }
}
