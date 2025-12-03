<?php

namespace App\Providers;

use App\Actions\File\UploadFileAction;
use App\Actions\Quote\CompareQuotesAction;
use App\Actions\Quote\ImportSupplierQuotesAction;
use App\Actions\RFQ\ImportRfqAction;
use App\Services\FileUploadService;
use App\Services\QuoteComparisonService;
use App\Services\RFQImportService;
use App\Services\SupplierQuoteImportService;
use Illuminate\Support\ServiceProvider;

/**
 * ActionServiceProvider
 * 
 * Registers all application actions in the service container.
 * This provider ensures that actions are properly instantiated with their dependencies.
 */
class ActionServiceProvider extends ServiceProvider
{
    /**
     * Register the application's actions
     */
    public function register(): void
    {
        // RFQ Actions
        $this->app->bind(ImportRfqAction::class, function ($app) {
            return new ImportRfqAction(
                $app->make(RFQImportService::class)
            );
        });

        // Quote Actions
        $this->app->bind(CompareQuotesAction::class, function ($app) {
            return new CompareQuotesAction(
                $app->make(QuoteComparisonService::class)
            );
        });

        $this->app->bind(ImportSupplierQuotesAction::class, function ($app) {
            return new ImportSupplierQuotesAction(
                $app->make(SupplierQuoteImportService::class)
            );
        });

        // File Actions
        $this->app->bind(UploadFileAction::class, function ($app) {
            return new UploadFileAction(
                $app->make(FileUploadService::class)
            );
        });
    }

    /**
     * Bootstrap the application's actions
     */
    public function boot(): void
    {
        // Any additional bootstrapping can be done here
    }
}
