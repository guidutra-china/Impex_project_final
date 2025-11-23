<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    /**
     * Listen for events from BOM relation manager
     */
    protected function getListeners(): array
    {
        return [
            'refresh-product-costs' => 'refreshFormData',
        ];
    }

    /**
     * Refresh form data when BOM costs are updated
     */
    public function refreshFormData(): void
    {
        // Refresh the record from database to get updated costs
        $this->record->refresh();
        
        // Optionally send a notification
        \Filament\Notifications\Notification::make()
            ->success()
            ->title('Costs Updated')
            ->body('Manufacturing cost summary has been refreshed.')
            ->send();
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
