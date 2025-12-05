<?php

namespace App\Filament\Resources\CommercialInvoices\Pages;

use App\Filament\Resources\CommercialInvoices\CommercialInvoiceResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewCommercialInvoice extends ViewRecord
{
    protected static string $resource = CommercialInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),

            Action::make('generate_pdf_original')
                ->label('PDF (Original)')
                ->icon(Heroicon::OutlinedDocumentText)
                ->color('primary')
                ->action(function () {
                    // TODO: Implement in Phase 4
                    $this->record->generatePDF('original');
                }),

            Action::make('generate_pdf_customs')
                ->label('PDF (Customs)')
                ->icon(Heroicon::OutlinedDocumentText)
                ->color('warning')
                ->action(function () {
                    // TODO: Implement in Phase 4
                    $this->record->generatePDF('customs');
                }),

            Action::make('generate_excel_original')
                ->label('Excel (Original)')
                ->icon(Heroicon::OutlinedTableCells)
                ->color('success')
                ->action(function () {
                    // TODO: Implement in Phase 5
                    $this->record->generateExcel('original');
                }),

            Action::make('generate_excel_customs')
                ->label('Excel (Customs)')
                ->icon(Heroicon::OutlinedTableCells)
                ->color('warning')
                ->action(function () {
                    // TODO: Implement in Phase 5
                    $this->record->generateExcel('customs');
                }),
        ];
    }
}
