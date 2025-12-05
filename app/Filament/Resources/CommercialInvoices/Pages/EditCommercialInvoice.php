<?php

namespace App\Filament\Resources\CommercialInvoices\Pages;

use App\Filament\Resources\CommercialInvoices\CommercialInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCommercialInvoice extends EditRecord
{
    protected static string $resource = CommercialInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Convert cents to decimal when loading form
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Convert items unit_price, commission, total from cents to decimal
        if (isset($data['items'])) {
            foreach ($data['items'] as $key => $item) {
                $data['items'][$key]['unit_price'] = ($item['unit_price'] ?? 0) / 100;
                $data['items'][$key]['commission'] = ($item['commission'] ?? 0) / 100;
                $data['items'][$key]['total'] = ($item['total'] ?? 0) / 100;
            }
        }

        return $data;
    }

    /**
     * Convert decimal to cents before saving
     */
    protected function mutateFormDataBeforeSave(array $data): array
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

    protected function getFooterWidgets(): array
    {
        return [
            \App\Filament\Widgets\RelatedDocumentsWidget::class,
        ];
    }
}
