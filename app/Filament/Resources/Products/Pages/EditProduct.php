<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Repositories\ProductRepository;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected ?ProductRepository $productRepository = null;

    public function mount(string|int $record): void
    {
        parent::mount($record);
        $this->productRepository = app(ProductRepository::class);
    }

    /**
     * Listen for events from BOM relation manager
     */
    protected function getListeners(): array
    {
        return [
            'refresh-product-costs' => 'refreshProductCosts',
        ];
    }

    /**
     * Refresh product costs when BOM is updated
     */
    public function refreshProductCosts(): void
    {
        // Refresh the record from database to get updated costs
        $this->record->refresh();
        
        // Send notification
        \Filament\Notifications\Notification::make()
            ->success()
            ->title('Costs Updated')
            ->body('Manufacturing cost summary has been refreshed.')
            ->send();
        
        // Force page reload to show updated costs
        $this->redirect(static::getUrl(['record' => $this->record]), navigate: false);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * Recalculate manufacturing cost after saving
     */
    protected function afterSave(): void
    {
        $this->record->calculateManufacturingCost();
    }
}
