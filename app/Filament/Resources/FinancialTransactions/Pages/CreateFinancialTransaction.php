<?php
namespace App\Filament\Resources\FinancialTransactions\Pages;
use App\Filament\Resources\FinancialTransactions\FinancialTransactionResource;
use Filament\Resources\Pages\CreateRecord;
class CreateFinancialTransaction extends CreateRecord
{
    protected static string $resource = FinancialTransactionResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['amount'] = (int) ($data['amount'] * 100);
        $rate = $data['exchange_rate_to_base'] ?? 1.0;
        $data['amount_base_currency'] = (int) ($data['amount'] * $rate);
        $data['created_by'] = auth()->id();
        return $data;
    }
}
