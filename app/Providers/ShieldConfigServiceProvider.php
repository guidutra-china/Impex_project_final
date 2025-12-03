<?php

namespace App\Providers;

use BezhanSalleh\FilamentShield\Resources\RoleResource;
use Illuminate\Support\ServiceProvider;

class ShieldConfigServiceProvider extends ServiceProvider
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
        // Customize Shield RoleResource navigation
        RoleResource::navigationGroup('Security');
        RoleResource::navigationSort(20);
        RoleResource::navigationLabel('Roles & Permissions');
        RoleResource::navigationIcon('heroicon-o-shield-check');
    }
}
