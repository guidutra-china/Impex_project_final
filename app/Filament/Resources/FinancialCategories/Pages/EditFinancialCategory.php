<?php

namespace App\Filament\Resources\FinancialCategories\Pages;

use App\Filament\Resources\FinancialCategories\FinancialCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFinancialCategory extends EditRecord
{
    protected static string $resource = FinancialCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => !$this->record->is_system),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
