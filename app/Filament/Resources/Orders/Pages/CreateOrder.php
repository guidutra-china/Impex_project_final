<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Repositories\OrderRepository;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected ?OrderRepository $orderRepository = null;

    public function mount(): void
    {
        parent::mount();
        $this->orderRepository = app(OrderRepository::class);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();
        
        return $data;
    }

    /**
     * Redirect to edit page after creation
     * This ensures values are displayed correctly (cents converted to decimal)
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
