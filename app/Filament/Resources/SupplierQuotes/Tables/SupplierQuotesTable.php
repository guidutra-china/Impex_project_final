<?php

namespace App\Filament\Resources\SupplierQuotes\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SupplierQuotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('quote_number')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('order.order_number')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('supplier.name')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'info' => 'sent',
                        'success' => 'accepted',
                        'danger' => 'rejected',
                    ]),

                TextColumn::make('currency.code')
                    ->label('Currency'),

                TextColumn::make('total_price_after_commission')
                    ->label('Total')
                    ->money(fn ($record) => $record->currency?->code ?? 'USD', divideBy: 100)
                    ->sortable(),

                TextColumn::make('locked_exchange_rate')
                    ->label('Rate')
                    ->numeric(decimalPlaces: 4)
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_latest')
                    ->label('Latest')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('valid_until')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'accepted' => 'Accepted',
                        'rejected' => 'Rejected',
                    ]),

                SelectFilter::make('order_id')
                    ->relationship('order', 'order_number')
                    ->label('Order'),

                SelectFilter::make('supplier_id')
                    ->relationship('supplier', 'name')
                    ->label('Supplier'),

                TernaryFilter::make('is_latest')
                    ->label('Latest Version Only'),
            ])
            ->actions([
                EditAction::make(),
                Action::make('calculate_commission')
                    ->label('Recalculate')
                    ->icon('heroicon-o-calculator')
                    ->action(function ($record) {
                        $record->calculateCommission();
                        $record->lockExchangeRate();
                    })
                    ->requiresConfirmation()
                    ->color('warning'),
                
                Action::make('create_purchase_order')
                    ->label('Create PO')
                    ->icon('heroicon-o-document-plus')
                    ->action(function ($record) {
                        // Create PO from Supplier Quote
                        $po = \App\Models\PurchaseOrder::create([
                            'po_number' => 'PO-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                            'revision_number' => 1,
                            'po_date' => now(),
                            'status' => 'draft',
                            'order_id' => $record->order_id,
                            'supplier_quote_id' => $record->id,
                            'supplier_id' => $record->supplier_id,
                            'currency_id' => $record->currency_id,
                            'exchange_rate' => $record->locked_exchange_rate ?? $record->currency?->exchange_rate ?? 1,
                            'base_currency_id' => \App\Models\Currency::where('code', 'USD')->first()?->id,
                            'subtotal' => 0,
                            'total' => 0,
                            'total_base_currency' => 0,
                        ]);
                        
                        // Create PO items from quote items
                        foreach ($record->items as $quoteItem) {
                            \App\Models\PurchaseOrderItem::create([
                                'purchase_order_id' => $po->id,
                                'product_id' => $quoteItem->product_id,
                                'product_name' => $quoteItem->product?->name ?? '',
                                'product_sku' => $quoteItem->product?->sku ?? '',
                                'quantity' => $quoteItem->quantity,
                                'unit_cost' => $quoteItem->unit_price_before_commission,
                                'total_cost' => $quoteItem->total_price_before_commission,
                                'notes' => $quoteItem->supplier_notes ?? '',
                            ]);
                        }
                        
                        // Recalculate totals
                        $po->recalculateTotals();
                        
                        // Redirect to edit PO
                        return redirect()->route('filament.admin.resources.purchase-orders.edit', ['record' => $po->id]);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Create Purchase Order')
                    ->modalDescription('This will create a new Purchase Order from this Supplier Quote.')
                    ->modalSubmitActionLabel('Create PO')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'accepted'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
