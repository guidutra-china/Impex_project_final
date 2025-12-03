<?php

namespace App\Filament\Resources\GeneratedDocuments\Pages;

use App\Filament\Resources\GeneratedDocuments\GeneratedDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGeneratedDocuments extends ListRecords
{
    protected static string $resource = GeneratedDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $this->dispatch('$refresh');
                }),
        ];
    }

    public function getTitle(): string
    {
        return 'Document History';
    }

    public function getHeading(): string
    {
        return 'Document History';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\GeneratedDocuments\Widgets\GeneratedDocumentsStatsWidget::class,
        ];
    }
}
