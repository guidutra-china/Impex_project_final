<?php

namespace App\Providers;

use App\Models\PurchaseOrder;
use App\Models\Shipment;
use App\Models\PackingBox;
use App\Models\CommercialInvoice;
use App\Observers\PurchaseOrderObserver;
use App\Observers\ShipmentObserver;
use App\Observers\PackingBoxObserver;
use App\Observers\CommercialInvoiceObserver;
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
        CommercialInvoice::observe(CommercialInvoiceObserver::class);
        Shipment::observe(ShipmentObserver::class);
        PackingBox::observe(PackingBoxObserver::class);
    }
}
