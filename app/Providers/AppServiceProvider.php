<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\Supplier;
use App\Observers\ClientObserver;
use App\Observers\SupplierObserver;
use App\Services\FileUploadService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(FileUploadService::class, function ($app) {
            return new FileUploadService();
        });
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
