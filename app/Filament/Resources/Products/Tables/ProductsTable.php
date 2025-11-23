<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->label('Avatar')
                    ->circular()
                    ->size(40)
                    ->default('No Picture'),

                TextColumn::make('name')
                    ->label('Product Name')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->weight('bold'),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('SKU copied')
                    ->copyMessageDuration(1500)
                    ->badge()
                    ->color('gray'),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => $record->category?->color ?? 'gray')
                    ->icon(fn ($record) => $record->category?->icon)
                    ->default('-'),

                TextColumn::make('brand')
                    ->label('Family')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->default('-'),

                TextColumn::make('price')
                    ->label('Price')
                    ->sortable()
                    ->money(fn ($record) => $record->currency?->code ?? 'USD', divideBy: 100)
                    ->default('-'),

                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable()
                    ->limit(20)
                    ->toggleable()
                    ->default('-'),

                TextColumn::make('client.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->limit(10)
                    ->toggleable()
                    ->default('-'),

                SelectColumn::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ])
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('moq')
                    ->label('MOQ')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn ($state, $record) => $state ? "{$state} {$record->moq_unit}" : '-'),


                TextColumn::make('lead_time_days')
                    ->label('Lead Time')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->suffix(' days')
                    ->default('-'),

                TextColumn::make('origin_country')
                    ->label('Origin')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->default('-'),

                TextColumn::make('hs_code')
                    ->label('HS Code')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable(),

                TextColumn::make('pcs_per_carton')
                    ->label('Pcs/Carton')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->default('-'),

                TextColumn::make('carton_cbm')
                    ->label('CBM')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->suffix(' m³')
                    ->default('-'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Category'),

                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),

                SelectFilter::make('supplier')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('client')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Customer'),

                SelectFilter::make('origin_country')
                    ->label('Country of Origin')
                    ->options(fn () => \App\Models\Product::query()
                        ->whereNotNull('origin_country')
                        ->distinct()
                        ->pluck('origin_country', 'origin_country')
                        ->toArray()
                    )
                    ->searchable(),

                TernaryFilter::make('has_moq')
                    ->label('Has MOQ')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('moq'),
                        false: fn ($query) => $query->whereNull('moq'),
                    ),

                TernaryFilter::make('has_photos')
                    ->label('Has Photos')
                    ->queries(
                        true: fn ($query) => $query->whereHas('photos'),
                        false: fn ($query) => $query->whereDoesntHave('photos'),
                    ),
            ])
            ->defaultSort('created_at', 'desc')
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);


    }
}
