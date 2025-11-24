<?php
namespace App\Filament\Resources\RecurringTransactions\Pages;
use App\Filament\Resources\RecurringTransactions\RecurringTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListRecurringTransactions extends ListRecords
{
    protected static string $resource = RecurringTransactionResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
