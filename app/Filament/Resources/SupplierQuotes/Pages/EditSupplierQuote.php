<?php

namespace App\Filament\Resources\SupplierQuotes\Pages;

use App\Filament\Resources\SupplierQuotes\SupplierQuoteResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSupplierQuote extends EditRecord
{
    protected static string $resource = SupplierQuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            Action::make('recalculate')
                ->label('Recalculate All')
                ->icon('heroicon-o-calculator')
                ->action(function () {
                    $this->record->lockExchangeRate();
                    $this->record->calculateCommission();
                })
                ->requiresConfirmation()
                ->color('warning'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();
        
        return $data;
    }
}
