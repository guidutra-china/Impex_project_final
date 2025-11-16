<?php

namespace App\Filament\Resources\SupplierQuotes\Components\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ComponentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->name),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'raw_material' => 'info',
                        'purchased_part' => 'success',
                        'sub_assembly' => 'warning',
                        'packaging' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'raw_material' => 'Raw Material',
                        'purchased_part' => 'Purchased Part',
                        'sub_assembly' => 'Sub-Assembly',
                        'packaging' => 'Packaging',
                        default => ucfirst($state),
                    })
                    ->sortable(),

                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable()
                    ->default('-')
                    ->toggleable(),

                TextColumn::make('total_cost_per_unit')
                    ->label('Total Cost/Unit')
                    ->formatStateUsing(function ($record) {
                        if (!$record->currency) {
                            return $record->total_cost_per_unit ? number_format($record->total_cost_per_unit / 100, 2) : '-';
                        }
                        $amount = $record->total_cost_per_unit ? $record->total_cost_per_unit / 100 : 0;
                        return $record->currency->symbol . number_format($amount, 2);
                    })
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(fn ($record) => ' ' . $record->unit_of_measure)
                    ->sortable()
                    ->color(fn ($record) => $record->isLowStock() ? 'danger' : null)
                    ->weight(fn ($record) => $record->isLowStock() ? 'bold' : null)
                    ->toggleable(),

                TextColumn::make('unit_of_measure')
                    ->label('UOM')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('bomItems_count')
                    ->label('Used In')
                    ->counts('bomItems')
                    ->badge()
                    ->color('info')
                    ->suffix(' products')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'raw_material' => 'Raw Material',
                        'purchased_part' => 'Purchased Part',
                        'sub_assembly' => 'Sub-Assembly',
                        'packaging' => 'Packaging',
                    ])
                    ->label('Component Type'),

                SelectFilter::make('supplier')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Supplier'),

                TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All components')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                TernaryFilter::make('low_stock')
                    ->label('Stock Level')
                    ->placeholder('All stock levels')
                    ->trueLabel('Low stock only')
                    ->falseLabel('Adequate stock')
                    ->queries(
                        true: fn ($query) => $query->lowStock(),
                        false: fn ($query) => $query->whereNotNull('reorder_level')
                                                    ->whereColumn('stock_quantity', '>', 'reorder_level'),
                    ),
            ])
            ->actions([
                // Actions will be added by the resource
            ])
            ->bulkActions([
                // Bulk actions will be added by the resource
            ])
            ->defaultSort('code', 'asc');
    }
}
