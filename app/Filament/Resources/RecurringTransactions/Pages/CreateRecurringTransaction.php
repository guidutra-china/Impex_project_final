<?php
namespace App\Filament\Resources\RecurringTransactions\Pages;
use App\Filament\Resources\RecurringTransactions\RecurringTransactionResource;
use Filament\Resources\Pages\CreateRecord;
class CreateRecurringTransaction extends CreateRecord
{
    protected static string $resource = RecurringTransactionResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['amount'] = (int) ($data['amount'] * 100);
        $data['created_by'] = auth()->id();
        return $data;
    }
}
