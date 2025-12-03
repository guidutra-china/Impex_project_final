<?php

namespace App\Providers;

use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\BaseRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Repositories\ClientRepository;
use App\Repositories\SupplierRepository;
use App\Repositories\FinancialTransactionRepository;
use App\Repositories\ProformaInvoiceRepository;
use App\Repositories\SupplierQuoteRepository;
use App\Repositories\SalesInvoiceRepository;
use App\Repositories\PurchaseOrderRepository;
use App\Repositories\DocumentRepository;
use App\Repositories\RFQRepository;
use App\Repositories\EventRepository;
use App\Repositories\ShipmentRepository;
use App\Repositories\CategoryRepository;
use Illuminate\Support\ServiceProvider;

/**
 * RepositoryServiceProvider
 * 
 * Registra todos os repositories no container de injeção de dependência.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Registrar repositories específicos
        $this->app->singleton(OrderRepository::class, function ($app) {
            return new OrderRepository();
        });

        $this->app->singleton(ProductRepository::class, function ($app) {
            return new ProductRepository();
        });

        $this->app->singleton(ClientRepository::class, function ($app) {
            return new ClientRepository();
        });

        $this->app->singleton(SupplierRepository::class, function ($app) {
            return new SupplierRepository();
        });

        $this->app->singleton(FinancialTransactionRepository::class, function ($app) {
            return new FinancialTransactionRepository();
        });

        $this->app->singleton(ProformaInvoiceRepository::class, function ($app) {
            return new ProformaInvoiceRepository();
        });

        $this->app->singleton(SupplierQuoteRepository::class, function ($app) {
            return new SupplierQuoteRepository();
        });

        $this->app->singleton(SalesInvoiceRepository::class, function ($app) {
            return new SalesInvoiceRepository();
        });

        $this->app->singleton(PurchaseOrderRepository::class, function ($app) {
            return new PurchaseOrderRepository();
        });

        $this->app->singleton(DocumentRepository::class, function ($app) {
            return new DocumentRepository();
        });

        $this->app->singleton(RFQRepository::class, function ($app) {
            return new RFQRepository();
        });

        $this->app->singleton(EventRepository::class, function ($app) {
            return new EventRepository();
        });

        $this->app->singleton(ShipmentRepository::class, function ($app) {
            return new ShipmentRepository();
        });

        $this->app->singleton(CategoryRepository::class, function ($app) {
            return new CategoryRepository();
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
