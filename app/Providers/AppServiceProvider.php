<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\QuoteItem;
use App\Models\Supplier;
use App\Observers\ClientObserver;
use App\Observers\QuoteItemObserver;
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

        // Register the ActionServiceProvider
        $this->app->register(ActionServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers
        Client::observe(ClientObserver::class);
        Supplier::observe(SupplierObserver::class);
        QuoteItem::observe(QuoteItemObserver::class);
    }
}
