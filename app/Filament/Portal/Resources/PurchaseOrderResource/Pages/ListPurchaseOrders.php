<?php

namespace App\Filament\Portal\Resources\PurchaseOrderResource\Pages;

use App\Filament\Portal\Resources\PurchaseOrderResource;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseOrders extends ListRecords
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action - POs are created by admin
        ];
    }
}
