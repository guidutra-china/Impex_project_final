<?php

namespace App\Filament\Resources\DocumentImports\Pages;

use App\Filament\Resources\DocumentImports\DocumentImportResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListDocumentImports extends ListRecords
{
    protected static string $resource = DocumentImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('new_import')
                ->label('New Import')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->url(fn (): string => DocumentImportResource::getUrl('create')),
        ];
    }
}
