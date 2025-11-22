<?php

namespace App\Filament\Resources\Shipments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ShipmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('shipment_number')
                    ->searchable(),
                TextColumn::make('salesOrder.id')
                    ->searchable(),
                TextColumn::make('purchaseOrder.id')
                    ->searchable(),
                TextColumn::make('shipment_type')
                    ->badge(),
                TextColumn::make('carrier')
                    ->searchable(),
                TextColumn::make('tracking_number')
                    ->searchable(),
                TextColumn::make('container_number')
                    ->searchable(),
                TextColumn::make('shipping_method')
                    ->badge(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('shipment_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('estimated_delivery_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('actual_delivery_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('shipping_cost')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('currency.name')
                    ->searchable(),
                TextColumn::make('total_weight')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_volume')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('notification_sent_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_by')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
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
            ]);
    }
}
