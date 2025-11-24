<?php

namespace App\Providers;

use App\Models\PurchaseOrder;
use App\Models\SalesInvoice;
use App\Observers\PurchaseOrderObserver;
use App\Observers\SalesInvoiceObserver;
use Illuminate\Support\ServiceProvider;

class ObserverServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        PurchaseOrder::observe(PurchaseOrderObserver::class);
        SalesInvoice::observe(SalesInvoiceObserver::class);
    }
}
