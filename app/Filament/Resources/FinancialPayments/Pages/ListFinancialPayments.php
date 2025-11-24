<?php

namespace App\Filament\Resources\FinancialPayments\Pages;

use App\Filament\Resources\FinancialPayments\FinancialPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFinancialPayments extends ListRecords
{
    protected static string $resource = FinancialPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
