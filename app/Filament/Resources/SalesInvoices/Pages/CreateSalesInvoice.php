<?php

namespace App\Filament\Resources\SalesInvoices\Pages;

use App\Filament\Resources\SalesInvoices\SalesInvoiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSalesInvoice extends CreateRecord
{
    protected static string $resource = SalesInvoiceResource::class;

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
}
