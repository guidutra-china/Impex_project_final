<?php
namespace App\Filament\Resources\FinancialTransactions\Pages;
use App\Filament\Resources\FinancialTransactions\FinancialTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditFinancialTransaction extends EditRecord
{
    protected static string $resource = FinancialTransactionResource::class;
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['amount'] = $data['amount'] / 100;
        return $data;
    }
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['amount'] = (int) ($data['amount'] * 100);
        $rate = $data['exchange_rate_to_base'] ?? 1.0;
        $data['amount_base_currency'] = (int) ($data['amount'] * $rate);
        return $data;
    }
}
