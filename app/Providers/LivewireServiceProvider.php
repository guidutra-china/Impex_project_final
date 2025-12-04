<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class LivewireServiceProvider extends ServiceProvider
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
        // Explicitly register RelationManager components to ensure Livewire can discover them
        // This helps prevent "ComponentNotFoundException" errors with custom RelationManagers
        
        Livewire::component(
            'app.filament.resources.shipments.relation-managers.shipment-containers-relation-manager',
            \App\Filament\Resources\Shipments\RelationManagers\ShipmentContainersRelationManager::class
        );
    }
}
