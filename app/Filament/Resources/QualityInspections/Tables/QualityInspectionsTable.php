<?php

namespace App\Filament\Resources\QualityInspections\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class QualityInspectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('inspection_number')
                    ->searchable(),
                TextColumn::make('inspectable_type')
                    ->searchable(),
                TextColumn::make('inspectable_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('inspection_type')
                    ->badge(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('result')
                    ->badge(),
                TextColumn::make('inspection_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('completed_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('inspector.name')
                    ->searchable(),
                TextColumn::make('inspector_name')
                    ->searchable(),
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
