<?php

namespace App\Filament\Resources\Shipments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ShipmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('shipment_number')
                    ->label('Shipment #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                BadgeColumn::make('shipment_type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'outbound' => 'Outbound',
                        'inbound' => 'Inbound',
                        default => $state,
                    })
                    ->colors([
                        'primary' => 'outbound',
                        'success' => 'inbound',
                    ]),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => str_replace('_', ' ', ucwords($state, '_')))
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => ['preparing', 'ready_to_ship'],
                        'info' => 'confirmed',
                        'primary' => ['picked_up', 'in_transit'],
                        'success' => 'delivered',
                        'danger' => ['cancelled', 'returned'],
                    ]),

                TextColumn::make('shipping_method')
                    ->label('Method')
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->badge()
                    ->colors([
                        'primary' => 'air',
                        'info' => 'sea',
                        'warning' => 'land',
                        'success' => 'courier',
                    ])
                    ->sortable(),

                TextColumn::make('carrier')
                    ->label('Carrier')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('tracking_number')
                    ->label('Tracking #')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                TextColumn::make('total_items')
                    ->label('Items')
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('total_quantity')
                    ->label('Qty')
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('total_boxes')
                    ->label('Boxes')
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(),

                BadgeColumn::make('packing_status')
                    ->label('Packing')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'not_packed' => 'Not Packed',
                        'partially_packed' => 'Partial',
                        'fully_packed' => 'Complete',
                        default => 'N/A',
                    })
                    ->colors([
                        'secondary' => 'not_packed',
                        'warning' => 'partially_packed',
                        'success' => 'fully_packed',
                    ])
                    ->toggleable(),

                TextColumn::make('total_weight')
                    ->label('Weight (kg)')
                    ->numeric(2)
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('total_volume')
                    ->label('Volume (m³)')
                    ->numeric(3)
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('shipment_date')
                    ->label('Ship Date')
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('estimated_arrival_date')
                    ->label('Est. Arrival')
                    ->date('Y-m-d')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('actual_delivery_date')
                    ->label('Delivered')
                    ->date('Y-m-d')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('shipping_cost')
                    ->label('Cost')
                    ->money('USD', 100)
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('currency.code')
                    ->label('Currency')
                    ->searchable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                TextColumn::make('confirmed_at')
                    ->label('Confirmed')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                TextColumn::make('confirmedBy.name')
                    ->label('Confirmed By')
                    ->searchable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                TextColumn::make('deleted_at')
                    ->label('Deleted')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->filters([
                SelectFilter::make('shipment_type')
                    ->label('Type')
                    ->options([
                        'outbound' => 'Outbound',
                        'inbound' => 'Inbound',
                    ]),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'preparing' => 'Preparing',
                        'ready_to_ship' => 'Ready to Ship',
                        'confirmed' => 'Confirmed',
                        'picked_up' => 'Picked Up',
                        'in_transit' => 'In Transit',
                        'customs_clearance' => 'Customs Clearance',
                        'out_for_delivery' => 'Out for Delivery',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                        'returned' => 'Returned',
                    ])
                    ->multiple(),

                SelectFilter::make('shipping_method')
                    ->label('Shipping Method')
                    ->options([
                        'air' => 'Air',
                        'sea' => 'Sea',
                        'land' => 'Land',
                        'courier' => 'Courier',
                        'rail' => 'Rail',
                    ]),

                SelectFilter::make('packing_status')
                    ->label('Packing Status')
                    ->options([
                        'not_packed' => 'Not Packed',
                        'partially_packed' => 'Partially Packed',
                        'fully_packed' => 'Fully Packed',
                    ]),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                \Filament\Actions\Action::make('commercialInvoice')
                    ->label('Commercial Invoice')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->visible(fn ($record) => $record->status !== 'draft' && $record->total_items > 0)
                    ->action(function ($record) {
                        \Filament\Notifications\Notification::make()
                            ->title('Feature Coming Soon')
                            ->body('Commercial Invoice generation will be implemented soon.')
                            ->info()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s'); // Auto-refresh every 30 seconds
    }
}
