<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\Supplier;
use App\Observers\ClientObserver;
use App\Observers\SupplierObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers
        Client::observe(ClientObserver::class);
        Supplier::observe(SupplierObserver::class);
    }
}
