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
                
                TextColumn::make('order.rfq_number')
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
