<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Repositories\ProductRepository;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected ?ProductRepository $productRepository = null;

    public function mount(): void
    {
        parent::mount();
        $this->productRepository = app(ProductRepository::class);
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
