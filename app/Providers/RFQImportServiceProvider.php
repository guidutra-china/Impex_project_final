<?php

namespace App\Providers;

use App\Services\RFQImportService;
use Illuminate\Support\ServiceProvider;

class RFQImportServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(RFQImportService::class, function ($app) {
            return new RFQImportService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
