<?php
namespace App\Filament\Resources\FinancialPayments\Pages;
use App\Filament\Resources\FinancialPayments\FinancialPaymentResource;
use Filament\Resources\Pages\CreateRecord;
class CreateFinancialPayment extends CreateRecord
{
    protected static string $resource = FinancialPaymentResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['amount'] = (int) ($data['amount'] * 100);
        $data['fee'] = (int) (($data['fee'] ?? 0) * 100);
        $data['net_amount'] = $data['amount'] - $data['fee'];
        $rate = $data['exchange_rate_to_base'] ?? 1.0;
        $data['amount_base_currency'] = (int) ($data['amount'] * $rate);
        $data['created_by'] = auth()->id();
        return $data;
    }
}
