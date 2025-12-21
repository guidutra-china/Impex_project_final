<?php

namespace App\Filament\Portal\Resources\ProformaInvoiceResource\Pages;

use App\Filament\Portal\Resources\ProformaInvoiceResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;

class ViewProformaInvoice extends ViewRecord
{
    protected static string $resource = ProformaInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_pdf')
                ->label('View PDF')
                ->icon('heroicon-o-document-text')
                ->url(fn () => route('public.proforma-invoice.show', ['token' => $this->record->public_token]))
                ->openUrlInNewTab()
                ->color('primary'),
            Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn () => route('public.proforma-invoice.download', ['token' => $this->record->public_token]))
                ->openUrlInNewTab()
                ->color('success'),
        ];
    }
}
