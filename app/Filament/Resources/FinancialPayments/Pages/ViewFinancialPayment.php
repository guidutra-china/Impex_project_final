<?php

namespace App\Filament\Resources\FinancialPayments\Pages;

use App\Filament\Resources\FinancialPayments\FinancialPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewFinancialPayment extends ViewRecord
{
    protected static string $resource = FinancialPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
