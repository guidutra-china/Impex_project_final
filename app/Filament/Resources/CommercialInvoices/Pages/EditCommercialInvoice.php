<?php

namespace App\Filament\Resources\CommercialInvoices\Pages;

use App\Filament\Resources\CommercialInvoices\CommercialInvoiceResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditCommercialInvoice extends EditRecord
{
    protected static string $resource = CommercialInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_pdf_original')
                ->label('PDF (Original)')
                ->icon(Heroicon::OutlinedDocumentText)
                ->color('primary')
                ->action(function () {
                    // TODO: Implement in Phase 4
                    $this->record->generatePDF('original');
                })
                ->disabled(fn () => $this->record->status === 'draft'),

            Action::make('generate_pdf_customs')
                ->label('PDF (Customs)')
                ->icon(Heroicon::OutlinedDocumentText)
                ->color('warning')
                ->action(function () {
                    // TODO: Implement in Phase 4
                    $this->record->generatePDF('customs');
                })
                ->disabled(fn () => $this->record->status === 'draft'),

            Action::make('generate_excel_original')
                ->label('Excel (Original)')
                ->icon(Heroicon::OutlinedTableCells)
                ->color('success')
                ->action(function () {
                    // TODO: Implement in Phase 5
                    $this->record->generateExcel('original');
                })
                ->disabled(fn () => $this->record->status === 'draft'),

            Action::make('generate_excel_customs')
                ->label('Excel (Customs)')
                ->icon(Heroicon::OutlinedTableCells)
                ->color('warning')
                ->action(function () {
                    // TODO: Implement in Phase 5
                    $this->record->generateExcel('customs');
                })
                ->disabled(fn () => $this->record->status === 'draft'),

            Action::make('issue')
                ->label('Issue Invoice')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->issue();
                    $this->refreshFormData(['status', 'issued_at', 'issued_by']);
                })
                ->visible(fn () => $this->record->canBeIssued()),

            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        // Recalculate totals after saving
        $this->record->calculateTotals();
    }
}
