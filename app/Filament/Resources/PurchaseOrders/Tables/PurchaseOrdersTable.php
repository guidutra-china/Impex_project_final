<?php

namespace App\Filament\Resources\PurchaseOrders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class PurchaseOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('po_number')
                    ->label('PO Number')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),
                
                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'sent',
                        'info' => 'confirmed',
                        'success' => fn ($state) => in_array($state, ['received', 'paid']),
                        'danger' => 'cancelled',
                    ])
                    ->sortable(),
                
                TextColumn::make('total')
                    ->label('Total')
                    ->money(fn ($record) => $record->currency?->code ?? 'USD')
                    ->sortable()
                    ->alignEnd()
                    ->weight('bold'),
                
                TextColumn::make('currency.code')
                    ->label('Currency')
                    ->badge()
                    ->sortable(),
                
                TextColumn::make('po_date')
                    ->label('PO Date')
                    ->date('M d, Y')
                    ->sortable(),
                
                TextColumn::make('expected_delivery_date')
                    ->label('Expected Delivery')
                    ->date('M d, Y')
                    ->sortable()
                    ->toggleable(),
                
                TextColumn::make('incoterm')
                    ->badge()
                    ->colors([
                        'primary' => fn ($state) => in_array($state, ['EXW', 'FCA']),
                        'success' => fn ($state) => in_array($state, ['FOB', 'FAS']),
                        'warning' => fn ($state) => in_array($state, ['CFR', 'CIF', 'CPT', 'CIP']),
                        'info' => fn ($state) => in_array($state, ['DAP', 'DPU', 'DDP']),
                    ])
                    ->toggleable(),
                
                TextColumn::make('order.order_number')
                    ->label('RFQ')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('supplierQuote.id')
                    ->label('Quote ID')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('revision_number')
                    ->label('Rev')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('shipping_cost')
                    ->label('Shipping')
                    ->money(fn ($record) => $record->currency?->code ?? 'USD')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money(fn ($record) => $record->currency?->code ?? 'USD')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('total_base_currency')
                    ->label('Total (Base)')
                    ->money(fn ($record) => $record->baseCurrency?->code ?? 'USD')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                IconColumn::make('shipping_included_in_price')
                    ->label('Ship. Incl.')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('paymentTerm.name')
                    ->label('Payment Terms')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('actual_delivery_date')
                    ->label('Actual Delivery')
                    ->date('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('sent_at')
                    ->label('Sent')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('confirmed_at')
                    ->label('Confirmed')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'confirmed' => 'Confirmed',
                        'received' => 'Received',
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
                    ])
                    ->multiple()
                    ->label('Status'),
                
                SelectFilter::make('supplier_id')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Supplier'),
                
                SelectFilter::make('currency_id')
                    ->relationship('currency', 'code')
                    ->searchable()
                    ->preload()
                    ->label('Currency'),
                
                SelectFilter::make('incoterm')
                    ->options([
                        'EXW' => 'EXW',
                        'FCA' => 'FCA',
                        'CPT' => 'CPT',
                        'CIP' => 'CIP',
                        'DAP' => 'DAP',
                        'DPU' => 'DPU',
                        'DDP' => 'DDP',
                        'FAS' => 'FAS',
                        'FOB' => 'FOB',
                        'CFR' => 'CFR',
                        'CIF' => 'CIF',
                    ])
                    ->multiple()
                    ->label('INCOTERM'),
                
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                
                // Status Transition Actions
                \Filament\Actions\Action::make('send_to_supplier')
                    ->label('Send to Supplier')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'sent',
                            'sent_at' => now(),
                        ]);
                        
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('PO sent to supplier')
                            ->body("Purchase Order {$record->po_number} has been sent.")
                            ->send();
                        
                        // TODO: Send email to supplier
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Send PO to Supplier')
                    ->modalDescription('This will mark the PO as sent and notify the supplier.')
                    ->visible(fn ($record) => $record->status === 'draft'),
                
                \Filament\Actions\Action::make('mark_as_confirmed')
                    ->label('Mark as Confirmed')
                    ->icon('heroicon-o-check-badge')
                    ->color('info')
                    ->action(function ($record) {
                        // Use DB update to only change status fields without affecting money fields
                        \DB::table('purchase_orders')
                            ->where('id', $record->id)
                            ->update([
                                'status' => 'confirmed',
                                'confirmed_at' => now(),
                                'updated_at' => now(),
                            ]);
                        
                        // Refresh the model to trigger Observer with correct values
                        $record->refresh();
                        
                        // Manually fire the updated event for Observer
                        $record->fireModelEvent('updated', false);
                        
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('PO confirmed')
                            ->body("Purchase Order {$record->po_number} has been confirmed by supplier.")
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'sent'),
                
                \Filament\Actions\Action::make('mark_as_received')
                    ->label('Mark as Received')
                    ->icon('heroicon-o-inbox-arrow-down')
                    ->color('success')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'received',
                            'received_at' => now(),
                            'actual_delivery_date' => now(),
                        ]);
                        
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('PO received')
                            ->body("Purchase Order {$record->po_number} has been received.")
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'confirmed'),
                
                \Filament\Actions\Action::make('mark_as_paid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'paid',
                            'paid_at' => now(),
                        ]);
                        
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('PO paid')
                            ->body("Purchase Order {$record->po_number} has been marked as paid.")
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'received'),
                
                \Filament\Actions\Action::make('create_invoice')
                    ->label('Create Invoice')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->action(function ($record) {
                        $invoice = \App\Models\PurchaseInvoice::create([
                            'invoice_number' => \App\Models\PurchaseInvoice::generateInvoiceNumber(),
                            'purchase_order_id' => $record->id,
                            'supplier_id' => $record->supplier_id,
                            'currency_id' => $record->currency_id,
                            'base_currency_id' => $record->base_currency_id,
                            'exchange_rate' => $record->exchange_rate,
                            'invoice_date' => now(),
                            'due_date' => now()->addDays(30),
                            'status' => 'draft',
                        ]);

                        // Copy items from PO
                        foreach ($record->items as $item) {
                            $invoice->items()->create([
                                'product_id' => $item->product_id,
                                'product_name' => $item->product_name,
                                'product_sku' => $item->product_sku,
                                'quantity' => $item->quantity,
                                'unit_cost' => $item->unit_cost,
                                'total_cost' => $item->total_cost,
                                'purchase_order_item_id' => $item->id,
                                'notes' => $item->notes,
                            ]);
                        }

                        // Recalculate totals
                        $invoice->recalculateTotals();

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Invoice created')
                            ->body("Purchase Invoice {$invoice->invoice_number} has been created from PO {$record->po_number}.")
                            ->send();

                        return redirect()->route('filament.admin.resources.purchase-invoices.edit', ['record' => $invoice->id]);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Create Invoice from PO')
                    ->modalDescription('This will create a new Purchase Invoice with all items from this PO.')
                    ->visible(fn ($record) => in_array($record->status, ['confirmed', 'received', 'paid'])),
                
                \Filament\Actions\Action::make('cancel_po')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('cancellation_reason')
                            ->label('Cancellation Reason')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'cancelled',
                            'cancelled_at' => now(),
                            'cancellation_reason' => $data['cancellation_reason'],
                        ]);
                        
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('PO cancelled')
                            ->body("Purchase Order {$record->po_number} has been cancelled.")
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->visible(fn ($record) => in_array($record->status, ['draft', 'sent', 'confirmed'])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('po_date', 'desc');
    }
}
