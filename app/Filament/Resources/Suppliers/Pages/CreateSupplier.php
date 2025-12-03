<?php

namespace App\Filament\Resources\Suppliers\Pages;

use App\Filament\Resources\Suppliers\SupplierResource;
use App\Repositories\SupplierRepository;
use Filament\Resources\Pages\CreateRecord;

class CreateSupplier extends CreateRecord
{
    protected static string $resource = SupplierResource::class;

    protected SupplierRepository $supplierRepository;

    public function __construct()
    {
        parent::__construct();
        $this->supplierRepository = app(SupplierRepository::class);
    }
}
