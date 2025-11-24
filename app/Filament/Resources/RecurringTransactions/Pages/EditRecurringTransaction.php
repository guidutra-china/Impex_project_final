<?php
namespace App\Filament\Resources\RecurringTransactions\Pages;
use App\Filament\Resources\RecurringTransactions\RecurringTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditRecurringTransaction extends EditRecord
{
    protected static string $resource = RecurringTransactionResource::class;
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
        return $data;
    }
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['amount'] = (int) ($data['amount'] * 100);
        return $data;
    }
}
