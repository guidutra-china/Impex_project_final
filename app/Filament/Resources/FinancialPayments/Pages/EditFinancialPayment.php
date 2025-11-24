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
        return [Actions\DeleteAction::make()];
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['amount'] = $data['amount'] / 100;
        $data['fee'] = $data['fee'] / 100;
        return $data;
    }
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['amount'] = (int) ($data['amount'] * 100);
        $data['fee'] = (int) (($data['fee'] ?? 0) * 100);
        $data['net_amount'] = $data['amount'] - $data['fee'];
        $rate = $data['exchange_rate_to_base'] ?? 1.0;
        $data['amount_base_currency'] = (int) ($data['amount'] * $rate);
        return $data;
    }
}
