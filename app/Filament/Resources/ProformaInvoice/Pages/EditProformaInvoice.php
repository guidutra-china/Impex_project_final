<?php

namespace App\Filament\Resources\ProformaInvoice\Pages;

use App\Filament\Resources\ProformaInvoice\ProformaInvoiceResource;
use App\Services\Export\PdfExportService;
use App\Services\Export\ExcelExportService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProformaInvoice extends EditRecord
{
    protected static string $resource = ProformaInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            
            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->canApprove())
                ->action(function ($record) {
                    $record->update([
                        'status' => 'approved',
                        'approved_at' => now(),
                        'approved_by' => auth()->id(),
                    ]);
                    
                    $this->notify('success', 'Proforma Invoice approved successfully');
                }),

            Actions\Action::make('reject')
                ->label('Reject')
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
                    $record->update([
                        'status' => 'rejected',
                        'rejected_at' => now(),
                        'rejection_reason' => $data['rejection_reason'],
                    ]);
                    
                    $this->notify('success', 'Proforma Invoice rejected');
                }),

            Actions\Action::make('mark_sent')
                ->label('Mark as Sent')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->status === 'draft')
                ->action(function ($record) {
                    $record->update([
                        'status' => 'sent',
                        'sent_at' => now(),
                    ]);
                    
                    $this->notify('success', 'Proforma Invoice marked as sent');
                }),

            Actions\Action::make('mark_deposit_received')
                ->label('Mark Deposit Received')
                ->icon('heroicon-o-currency-dollar')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->deposit_required && !$record->deposit_received)
                ->form([
                    \Filament\Forms\Components\TextInput::make('deposit_payment_method')
                        ->label('Payment Method')
                        ->required(),
                    \Filament\Forms\Components\TextInput::make('deposit_payment_reference')
                        ->label('Payment Reference'),
                ])
                ->action(function ($record, array $data) {
                    $record->update([
                        'deposit_received' => true,
                        'deposit_received_at' => now(),
                        'deposit_payment_method' => $data['deposit_payment_method'],
                        'deposit_payment_reference' => $data['deposit_payment_reference'] ?? null,
                    ]);
                    
                    $this->notify('success', 'Deposit marked as received');
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        return $data;
    }

    private function notify(string $type, string $message): void
    {
        \Filament\Notifications\Notification::make()
            ->title($message)
            ->{$type}()
            ->send();
    }

    protected function getFooterWidgets(): array
    {
        return [
            \App\Filament\Widgets\RelatedDocumentsWidget::class,
        ];
    }
}
