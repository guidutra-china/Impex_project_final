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

            Actions\Action::make('create_purchase_orders')
                ->label('Create Purchase Orders')
                ->icon('heroicon-o-document-plus')
                ->color('success')
                ->visible(fn ($record) => $record->status === 'approved')
                ->action(function ($record) {
                    $this->handleCreatePurchaseOrders($record);
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

    /**
     * Handle creating purchase orders from proforma invoice
     * 
     * @param $record Proforma Invoice record
     */
    protected function handleCreatePurchaseOrders($record): void
    {
        try {
            // Group items by supplier_quote_id
            $itemsBySupplier = $record->items()
                ->with(['supplierQuote.supplier', 'product'])
                ->get()
                ->groupBy('supplier_quote_id');

            if ($itemsBySupplier->isEmpty()) {
                Notification::make()
                    ->warning()
                    ->title('No Items Found')
                    ->body('This proforma invoice has no items to create purchase orders from.')
                    ->send();
                return;
            }

            $createdPOs = [];
            $errors = [];

            foreach ($itemsBySupplier as $supplierQuoteId => $items) {
                try {
                    $supplierQuote = $items->first()->supplierQuote;
                    
                    if (!$supplierQuote) {
                        $errors[] = "Supplier quote not found for some items";
                        continue;
                    }

                    // Check if PO already exists for this supplier quote
                    $existingPO = \App\Models\PurchaseOrder::where('supplier_quote_id', $supplierQuoteId)
                        ->where('proforma_invoice_id', $record->id)
                        ->first();

                    if ($existingPO) {
                        $errors[] = "PO already exists for {$supplierQuote->supplier->name} (PO: {$existingPO->po_number})";
                        continue;
                    }

                    // Create Purchase Order
                    $po = \App\Models\PurchaseOrder::create([
                        'revision_number' => 1,
                        'po_date' => now(),
                        'status' => 'draft',
                        'order_id' => $supplierQuote->order_id,
                        'supplier_quote_id' => $supplierQuoteId,
                        'proforma_invoice_id' => $record->id,
                        'supplier_id' => $supplierQuote->supplier_id,
                        'currency_id' => $supplierQuote->currency_id,
                        'exchange_rate' => $supplierQuote->locked_exchange_rate ?? 1,
                        'base_currency_id' => \App\Models\Currency::where('code', 'USD')->first()?->id,
                        'subtotal' => 0,
                        'total' => 0,
                        'total_base_currency' => 0,
                        'created_by' => auth()->id(),
                    ]);

                    // Create PO items from proforma items
                    foreach ($items as $proformaItem) {
                        \App\Models\PurchaseOrderItem::create([
                            'purchase_order_id' => $po->id,
                            'product_id' => $proformaItem->product_id,
                            'product_name' => $proformaItem->product_name ?? $proformaItem->product?->name ?? '',
                            'product_sku' => $proformaItem->product_sku ?? $proformaItem->product?->sku ?? '',
                            'quantity' => $proformaItem->quantity,
                            'unit_cost' => $proformaItem->unit_price,
                            'total_cost' => $proformaItem->total,
                            'notes' => $proformaItem->notes ?? '',
                        ]);
                    }

                    // Recalculate totals
                    $po->recalculateTotals();

                    $createdPOs[] = [
                        'po' => $po,
                        'supplier' => $supplierQuote->supplier->name,
                    ];

                } catch (\Exception $e) {
                    $errors[] = "Error creating PO: {$e->getMessage()}";
                    \Log::error('Error creating PO from Proforma', [
                        'proforma_id' => $record->id,
                        'supplier_quote_id' => $supplierQuoteId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Show results
            if (!empty($createdPOs)) {
                $poList = collect($createdPOs)->map(fn($item) => 
                    "{$item['po']->po_number} for {$item['supplier']}"
                )->join(', ');

                Notification::make()
                    ->success()
                    ->title('Purchase Orders Created')
                    ->body("Created {count($createdPOs)} PO(s): {$poList}")
                    ->send();

                // Redirect to the first PO
                return redirect()->route('filament.admin.resources.purchase-orders.edit', [
                    'record' => $createdPOs[0]['po']->id
                ]);
            }

            if (!empty($errors)) {
                Notification::make()
                    ->warning()
                    ->title('Some Issues Occurred')
                    ->body(implode("\n", $errors))
                    ->send();
            }

        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error Creating Purchase Orders')
                ->body($e->getMessage())
                ->send();

            \Log::error('Error in handleCreatePurchaseOrders', [
                'proforma_id' => $record->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
