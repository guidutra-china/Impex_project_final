<?php

namespace App\Filament\Widgets;

use App\Repositories\SalesInvoiceRepository;
use App\Repositories\PurchaseOrderRepository;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinancialOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected ?SalesInvoiceRepository $salesInvoiceRepository = null;
    protected ?PurchaseOrderRepository $purchaseOrderRepository = null;

    public function mount(): void
    {
        $this->salesInvoiceRepository = app(SalesInvoiceRepository::class);
        $this->purchaseOrderRepository = app(PurchaseOrderRepository::class);
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
        return auth()->user()->can('View:FinancialOverviewWidget');
    }
    
    protected function getStats(): array
    {
        // Ensure repositories are initialized
        if (!isset($this->salesInvoiceRepository) || !$this->salesInvoiceRepository) {
            $this->salesInvoiceRepository = app(SalesInvoiceRepository::class);
        }
        if (!isset($this->purchaseOrderRepository) || !$this->purchaseOrderRepository) {
            $this->purchaseOrderRepository = app(PurchaseOrderRepository::class);
        }
        
        $user = auth()->user();
        
        // Check if user can see all clients
        $canSeeAll = $user->roles()->where('can_see_all', true)->exists();
        
        // ========================================
        // CONTAS A RECEBER (Sales Invoices)
        // ========================================
        
        // Invoices pending payment (not paid, not cancelled)
        $invoicesPending = $this->salesInvoiceRepository->countPending();
        
        // Total to receive (pending invoices)
        $totalToReceive = $this->salesInvoiceRepository->getTotalPending() / 100; // Convert from cents
        
        // Invoices overdue
        $invoicesOverdue = $this->salesInvoiceRepository->countOverdue();
        
        // Total overdue
        $totalOverdue = $this->salesInvoiceRepository->getTotalOverdue() / 100; // Convert from cents
        
        // Invoices due in next 30 days
        $invoicesDueSoon = $this->salesInvoiceRepository->countDueSoon(30);
        
        // ========================================
        // CONTAS A PAGAR (Purchase Orders)
        // ========================================
        
        // POs pending payment (active but not fully paid)
        $posPending = $this->purchaseOrderRepository->countActive();
        
        // Total to pay (active POs)
        $totalToPay = $this->purchaseOrderRepository->getTotalActive() / 100; // Convert from cents
        
        // ========================================
        // FLUXO DE CAIXA (Cash Flow)
        // ========================================
        
        // Projected cash flow = to receive - to pay
        $cashFlow = $totalToReceive - $totalToPay;
        
        // ========================================
        // VENDAS DO MÊS (This Month Sales)
        // ========================================
        
        $thisMonthSales = $this->salesInvoiceRepository->getThisMonthTotal() / 100;
        $lastMonthSales = $this->salesInvoiceRepository->getLastMonthTotal() / 100;
        
        // Calculate trend
        $salesTrend = $this->salesInvoiceRepository->calculateSalesTrend();
        
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
            $count = $this->salesInvoiceRepository->getModel()
                ->whereDate('invoice_date', $date)
                ->count();
            $data[] = $count;
        }
        
        return $data;
    }
}
