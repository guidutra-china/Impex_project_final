<?php

namespace App\Filament\Resources\CustomerReceipts\Pages;

use App\Filament\Resources\CustomerReceipts\CustomerReceiptResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomerReceipt extends EditRecord
{
    protected static string $resource = CustomerReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
