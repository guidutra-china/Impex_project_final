<?php

namespace App\Filament\Resources\CommercialInvoices\Pages;

use App\Filament\Resources\CommercialInvoices\CommercialInvoiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCommercialInvoice extends CreateRecord
{
    protected static string $resource = CommercialInvoiceResource::class;

    /**
     * Convert decimal values to cents before saving
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Convert items unit_price, commission, total to cents
        if (isset($data['items'])) {
            foreach ($data['items'] as $key => $item) {
                $data['items'][$key]['unit_price'] = (int) round(($item['unit_price'] ?? 0) * 100);
                $data['items'][$key]['commission'] = (int) round(($item['commission'] ?? 0) * 100);
                $data['items'][$key]['total'] = (int) round(($item['total'] ?? 0) * 100);
            }
        }

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
