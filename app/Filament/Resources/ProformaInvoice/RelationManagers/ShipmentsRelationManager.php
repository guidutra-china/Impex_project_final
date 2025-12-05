<?php

namespace App\Filament\Resources\ProformaInvoice\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Support\Enums\FontWeight;

class ShipmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'shipments';

    protected static ?string $title = 'Related Shipments';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('shipment_number')
            ->columns([
                TextColumn::make('shipment_number')
                    ->label('Shipment #')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->copyable()
                    ->copyMessage('Shipment number copied')
                    ->copyMessageDuration(1500),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'preparing' => 'warning',
                        'ready' => 'info',
                        'in_transit' => 'primary',
                        'arrived' => 'success',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state)))
                    ->sortable(),

                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('containers_count')
                    ->label('Containers')
                    ->counts('containers')
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                TextColumn::make('total_items')
                    ->label('Total Items')
                    ->getStateUsing(fn ($record) => $record->items()->sum('quantity_to_ship'))
                    ->alignCenter()
                    ->numeric(),

                TextColumn::make('total_weight')
                    ->label('Weight (kg)')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('total_volume')
                    ->label('Volume (m³)')
                    ->numeric(decimalPlaces: 4)
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('shipping_method')
                    ->label('Method')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sea' => 'info',
                        'air' => 'warning',
                        'land' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => strtoupper($state))
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('etd')
                    ->label('ETD')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('eta')
                    ->label('ETA')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // No create action - shipments are created from the shipment resource
            ])
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn ($record) => route('filament.admin.resources.shipments.view', ['record' => $record->id]))
                    ->openUrlInNewTab(),

                Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->url(fn ($record) => route('filament.admin.resources.shipments.edit', ['record' => $record->id]))
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => in_array($record->status, ['draft', 'preparing'])),
            ])
            ->bulkActions([
                // No bulk actions for shipments in this context
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No shipments yet')
            ->emptyStateDescription('Create a shipment from the Shipments resource to link items from this Proforma Invoice')
            ->emptyStateIcon('heroicon-o-truck');
    }
}
