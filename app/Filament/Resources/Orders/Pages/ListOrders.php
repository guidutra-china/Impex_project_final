<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Repositories\OrderRepository;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected ?OrderRepository $orderRepository = null;

    public function mount(): void
    {
        parent::mount();
        $this->orderRepository = app(OrderRepository::class);
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
