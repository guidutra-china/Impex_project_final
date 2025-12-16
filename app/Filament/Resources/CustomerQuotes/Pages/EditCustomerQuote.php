<?php

namespace App\Filament\Resources\CustomerQuotes\Pages;

use App\Filament\Resources\CustomerQuotes\CustomerQuoteResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditCustomerQuote extends EditRecord
{
    protected static string $resource = CustomerQuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')
                ->label('View Quote')
                ->icon('heroicon-o-eye')
                ->url(fn () => $this->getResource()::getUrl('view', ['record' => $this->record])),
            
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // No need to update created_by on edit
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
