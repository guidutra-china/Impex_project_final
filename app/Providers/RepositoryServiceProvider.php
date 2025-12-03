<?php

namespace App\Providers;

use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\BaseRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Repositories\ClientRepository;
use App\Repositories\SupplierRepository;
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
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
