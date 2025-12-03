<?php

namespace App\Filament\Resources\DocumentHistoryResource\Pages;

use App\Filament\Resources\DocumentHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDocumentHistory extends ListRecords
{
    protected static string $resource = DocumentHistoryResource::class;

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
            \App\Filament\Resources\DocumentHistoryResource\Widgets\DocumentHistoryStatsWidget::class,
        ];
    }
}
