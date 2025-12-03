<?php

namespace App\Filament\Resources\PurchaseOrders\Pages;

use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseOrder extends EditRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /**
     * Convert cents to decimal when loading form
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Convert items unit_cost, total_cost, selling_price, selling_total from cents to decimal
        if (isset($data['items'])) {
            foreach ($data['items'] as $key => $item) {
                $data['items'][$key]['unit_cost'] = ($item['unit_cost'] ?? 0) / 100;
                $data['items'][$key]['total_cost'] = ($item['total_cost'] ?? 0) / 100;
                if (isset($item['selling_price'])) {
                    $data['items'][$key]['selling_price'] = $item['selling_price'] / 100;
                }
                if (isset($item['selling_total'])) {
                    $data['items'][$key]['selling_total'] = $item['selling_total'] / 100;
                }
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
