<?php

namespace App\Filament\Resources\Categories\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Category Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon(fn ($record) => $record->icon)
                    ->badge()
                    ->color(fn ($record) => $record->color ?? 'gray'),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->toggleable(),

                TextColumn::make('products_count')
                    ->label('Products')
                    ->counts('products')
                    ->sortable()
                    ->badge()
                    ->color('success'),

                TextColumn::make('categoryFeatures_count')
                    ->label('Features')
                    ->counts('categoryFeatures')
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label(__('common.active'))
                    ->boolean()
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->label(__('fields.order'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All categories')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order');
    }
}
