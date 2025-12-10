<?php

namespace App\Filament\Resources\ImportHistories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ImportHistoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->searchable(),
                TextColumn::make('file_name')
                    ->searchable(),
                TextColumn::make('file_type')
                    ->searchable(),
                TextColumn::make('file_path')
                    ->searchable(),
                TextColumn::make('file_size')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('import_type')
                    ->searchable(),
                TextColumn::make('document_type')
                    ->searchable(),
                TextColumn::make('supplier_name')
                    ->searchable(),
                TextColumn::make('supplier_email')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('total_rows')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('success_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('updated_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('skipped_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('error_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('warning_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('analyzed_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('imported_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
