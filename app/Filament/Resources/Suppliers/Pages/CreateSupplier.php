<?php

namespace App\Filament\Resources\Suppliers\Pages;

use App\Filament\Resources\Suppliers\SupplierResource;
use App\Repositories\SupplierRepository;
use Filament\Resources\Pages\CreateRecord;

class CreateSupplier extends CreateRecord
{
    protected static string $resource = SupplierResource::class;

    protected ?SupplierRepository $supplierRepository = null;

    public function mount(): void
    {
        parent::mount();
        $this->supplierRepository = app(SupplierRepository::class);
    }
}
