<?php

namespace App\Filament\Resources\ProformaInvoice\Pages;

use App\Filament\Resources\ProformaInvoice\ProformaInvoiceResource;
use App\Repositories\ProformaInvoiceRepository;
use App\Services\Export\PdfExportService;
use App\Services\Export\ExcelExportService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditProformaInvoice extends EditRecord
{
    protected static string $resource = ProformaInvoiceResource::class;

    protected function getProformaRepository(): ProformaInvoiceRepository
    {
        return app(ProformaInvoiceRepository::class);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            
            Actions\Action::make('approve')
                ->label(__('common.approved'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->canApprove())
                ->action(function ($record) {
                    $this->handleApprove($record);
                }),

            Actions\Action::make('reject')
                ->label(__('common.rejected'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->canReject())
                ->form([
                    \Filament\Forms\Components\Textarea::make('rejection_reason')
                        ->label('Rejection Reason')
                        ->required()
                        ->rows(3),
                ])
                ->action(function ($record, array $data) {
                    $this->handleReject($record, $data);
                }),

            Actions\Action::make('mark_sent')
                ->label('Mark as Sent')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->status === 'draft')
                ->action(function ($record) {
                    $this->handleMarkSent($record);
                }),

            Actions\Action::make('mark_deposit_received')
                ->label('Mark Deposit Received')
                ->icon('heroicon-o-currency-dollar')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->deposit_required && !$record->deposit_received)
                ->form([
                    \Filament\Forms\Components\TextInput::make('deposit_payment_method')
                        ->label(__('fields.payment_method'))
                        ->required(),
                    \Filament\Forms\Components\TextInput::make('deposit_payment_reference')
                        ->label(__('fields.payment_reference')),
                ])
                ->action(function ($record, array $data) {
                    $this->handleMarkDepositReceived($record, $data);
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();
        
        return $data;
    }

    protected function getFooterWidgets(): array
    {
        return [
            \App\Filament\Widgets\RelatedDocumentsWidget::class,
        ];
    }

    /**
     * Manipula a aprovação de Proforma Invoice
     * 
     * @param $record Registro da Proforma Invoice
     */
    protected function handleApprove($record): void
    {
        try {
            $this->getProformaRepository()->approve($record->id, auth()->id());
            
            Notification::make()
                ->success()
                ->title('Proforma Invoice approved')
                ->body('The proforma invoice has been approved successfully.')
                ->send();

            // Refresh the record
            $this->record->refresh();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error approving Proforma Invoice')
                ->body($e->getMessage())
                ->send();

            \Log::error('Erro ao aprovar Proforma Invoice', [
                'id' => $record->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Manipula a rejeição de Proforma Invoice
     * 
     * @param $record Registro da Proforma Invoice
     * @param array $data Dados da rejeição
     */
    protected function handleReject($record, array $data): void
    {
        try {
            $this->proformaRepository->reject($record->id, $data['rejection_reason']);
            
            Notification::make()
                ->success()
                ->title('Proforma Invoice rejected')
                ->body('The proforma invoice has been rejected.')
                ->send();

            // Refresh the record
            $this->record->refresh();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error rejecting Proforma Invoice')
                ->body($e->getMessage())
                ->send();

            \Log::error('Erro ao rejeitar Proforma Invoice', [
                'id' => $record->id,
                'reason' => $data['rejection_reason'],
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Manipula marcar como enviada
     * 
     * @param $record Registro da Proforma Invoice
     */
    protected function handleMarkSent($record): void
    {
        try {
            $this->proformaRepository->markAsSent($record->id);
            
            Notification::make()
                ->success()
                ->title('Proforma Invoice marked as sent')
                ->body('The proforma invoice has been marked as sent.')
                ->send();

            // Refresh the record
            $this->record->refresh();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error marking as sent')
                ->body($e->getMessage())
                ->send();

            \Log::error('Erro ao marcar Proforma Invoice como enviada', [
                'id' => $record->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Manipula marcar depósito como recebido
     * 
     * @param $record Registro da Proforma Invoice
     * @param array $data Dados do depósito
     */
    protected function handleMarkDepositReceived($record, array $data): void
    {
        try {
            $this->proformaRepository->markDepositReceived($record->id, [
                'deposit_payment_method' => $data['deposit_payment_method'],
                'deposit_payment_reference' => $data['deposit_payment_reference'] ?? null,
            ]);
            
            Notification::make()
                ->success()
                ->title('Deposit marked as received')
                ->body('The deposit has been marked as received.')
                ->send();

            // Refresh the record
            $this->record->refresh();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error marking deposit as received')
                ->body($e->getMessage())
                ->send();

            \Log::error('Erro ao marcar depósito como recebido', [
                'id' => $record->id,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
