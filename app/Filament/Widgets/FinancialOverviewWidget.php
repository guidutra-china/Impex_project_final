<?php

namespace App\Filament\Widgets;

use App\Models\SalesInvoice;
use App\Models\PurchaseOrder;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class FinancialOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    
    public static function canView(): bool
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return false;
        }
        
        // Check permission using Shield's actual format (uses separator from config)
        return auth()->user()->can('View:FinancialOverviewWidget');
    }
    
    protected function getStats(): array
    {
        $user = auth()->user();
        
        // Check if user can see all clients
        $canSeeAll = $user->roles()->where('can_see_all', true)->exists();
        
        // ========================================
        // CONTAS A RECEBER (Sales Invoices)
        // ========================================
        
        // Invoices pending payment (not paid, not cancelled)
        $invoicesPending = SalesInvoice::query()
            ->whereIn('status', ['draft', 'sent', 'overdue'])
            ->count();
        
        // Total to receive (pending invoices)
        $totalToReceive = SalesInvoice::query()
            ->whereIn('status', ['draft', 'sent', 'overdue'])
            ->sum(DB::raw('COALESCE(total_base_currency, 0)'));
        
        $totalToReceive = $totalToReceive / 100; // Convert from cents
        
        // Invoices overdue
        $invoicesOverdue = SalesInvoice::query()
            ->where('status', 'overdue')
            ->count();
        
        // Total overdue
        $totalOverdue = SalesInvoice::query()
            ->where('status', 'overdue')
            ->sum(DB::raw('COALESCE(total_base_currency, 0)'));
        
        $totalOverdue = $totalOverdue / 100; // Convert from cents
        
        // Invoices due in next 30 days
        $invoicesDueSoon = SalesInvoice::query()
            ->whereIn('status', ['sent'])
            ->whereBetween('due_date', [now(), now()->addDays(30)])
            ->count();
        
        // ========================================
        // CONTAS A PAGAR (Purchase Orders)
        // ========================================
        
        // POs pending payment (active but not fully paid)
        $posPending = PurchaseOrder::query()
            ->whereIn('status', ['approved', 'sent', 'confirmed', 'in_production', 'partially_received'])
            ->count();
        
        // Total to pay (active POs)
        $totalToPay = PurchaseOrder::query()
            ->whereIn('status', ['approved', 'sent', 'confirmed', 'in_production', 'partially_received'])
            ->sum(DB::raw('COALESCE(total_base_currency, 0)'));
        
        $totalToPay = $totalToPay / 100; // Convert from cents
        
        // ========================================
        // FLUXO DE CAIXA (Cash Flow)
        // ========================================
        
        // Projected cash flow = to receive - to pay
        $cashFlow = $totalToReceive - $totalToPay;
        
        // ========================================
        // VENDAS DO MÊS (This Month Sales)
        // ========================================
        
        $thisMonthSales = SalesInvoice::query()
            ->whereYear('invoice_date', now()->year)
            ->whereMonth('invoice_date', now()->month)
            ->sum(DB::raw('COALESCE(total_base_currency, 0)'));
        
        $thisMonthSales = $thisMonthSales / 100;
        
        // Last month sales
        $lastMonthSales = SalesInvoice::query()
            ->whereYear('invoice_date', now()->subMonth()->year)
            ->whereMonth('invoice_date', now()->subMonth()->month)
            ->sum(DB::raw('COALESCE(total_base_currency, 0)'));
        
        $lastMonthSales = $lastMonthSales / 100;
        
        // Calculate trend
        $salesTrend = $lastMonthSales > 0 
            ? round((($thisMonthSales - $lastMonthSales) / $lastMonthSales) * 100, 1)
            : 0;
        
        // ========================================
        // FORMAT VALUES
        // ========================================
        
        $formattedToReceive = 'R$ ' . number_format($totalToReceive, 2, ',', '.');
        $formattedToPay = 'R$ ' . number_format($totalToPay, 2, ',', '.');
        $formattedCashFlow = 'R$ ' . number_format($cashFlow, 2, ',', '.');
        $formattedThisMonthSales = 'R$ ' . number_format($thisMonthSales, 2, ',', '.');
        
        $scopeDescription = $canSeeAll ? 'All clients' : 'Your clients';
        
        return [
            Stat::make('Accounts Receivable', $formattedToReceive)
                ->description("{$invoicesPending} pending invoices")
                ->descriptionIcon('heroicon-o-arrow-trending-up')
                ->color('success')
                ->chart($this->getReceivablesChart()),
            
            Stat::make('Accounts Payable', $formattedToPay)
                ->description("{$posPending} active POs")
                ->descriptionIcon('heroicon-o-arrow-trending-down')
                ->color('danger'),
            
            Stat::make('Projected Cash Flow', $formattedCashFlow)
                ->description('Receivable - Payable')
                ->descriptionIcon($cashFlow >= 0 ? 'heroicon-o-check-circle' : 'heroicon-o-exclamation-triangle')
                ->color($cashFlow >= 0 ? 'success' : 'warning'),
            
            Stat::make('Overdue Invoices', $invoicesOverdue)
                ->description('R$ ' . number_format($totalOverdue, 2, ',', '.'))
                ->descriptionIcon('heroicon-o-exclamation-circle')
                ->color($invoicesOverdue > 0 ? 'danger' : 'success'),
                // ->url($invoicesOverdue > 0 ? route('filament.admin.resources.sales-invoices.sales-invoices.index') : null)
            
            Stat::make('Due in 30 Days', $invoicesDueSoon)
                ->description('Invoices due soon')
                ->descriptionIcon('heroicon-o-calendar')
                ->color($invoicesDueSoon > 0 ? 'warning' : 'gray'),
            
            Stat::make('Sales This Month', $formattedThisMonthSales)
                ->description($salesTrend >= 0 ? "+{$salesTrend}% vs last month" : "{$salesTrend}% vs last month")
                ->descriptionIcon($salesTrend >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                ->color($salesTrend >= 0 ? 'success' : 'danger'),
        ];
    }
    
    /**
     * Get receivables chart for last 7 days
     */
    protected function getReceivablesChart(): array
    {
        $data = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $count = SalesInvoice::whereDate('invoice_date', $date)->count();
            $data[] = $count;
        }
        
        return $data;
    }
}
