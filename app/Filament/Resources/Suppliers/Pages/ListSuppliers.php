<?php

namespace App\Filament\Resources\Suppliers\Pages;

use App\Filament\Resources\Suppliers\SupplierResource;
use App\Repositories\SupplierRepository;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListSuppliers extends ListRecords
{
    protected static string $resource = SupplierResource::class;

    protected SupplierRepository $supplierRepository;

    public function __construct()
    {
        parent::__construct();
        $this->supplierRepository = app(SupplierRepository::class);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * Override getEloquentQuery to use the repository for filtering and searching.
     * This allows the repository to handle the query logic while Filament handles the UI.
     */
    protected function getEloquentQuery(): Builder
    {
        // Get the base query from the model
        $query = parent::getEloquentQuery();
        
        // Apply any repository-specific filters if needed
        // For now, we're maintaining the default behavior
        return $query;
    }
}
