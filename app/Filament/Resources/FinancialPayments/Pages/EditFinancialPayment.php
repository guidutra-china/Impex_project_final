<?php

namespace App\Filament\Resources\FinancialPayments\Pages;

use App\Filament\Resources\FinancialPayments\FinancialPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFinancialPayment extends EditRecord
{
    protected static string $resource = FinancialPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Convert from centavos to display
        $data['amount'] = $data['amount'] / 100;
        $data['fee'] = $data['fee'] / 100;
        
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Convert to centavos
        $data['amount'] = (int) ($data['amount'] * 100);
        $data['fee'] = (int) (($data['fee'] ?? 0) * 100);
        
        // Calculate net_amount
        $data['net_amount'] = $data['amount'] - $data['fee'];
        
        // Calculate base currency amount
        $rate = $data['exchange_rate_to_base'] ?? 1.0;
        $data['amount_base_currency'] = (int) ($data['amount'] * $rate);
        
        return $data;
    }
}
