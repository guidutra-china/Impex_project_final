<?php

namespace App\Filament\Resources\PackingUnits\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ContainerTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('dimensions')
                    ->label('Dimensions (L×W×H)')
                    ->getStateUsing(fn ($record) => sprintf(
                        '%.2f × %.2f × %.2f m',
                        $record->length,
                        $record->width,
                        $record->height
                    ))
                    ->sortable(['length', 'width', 'height']),

                TextColumn::make('max_volume')
                    ->label('Volume')
                    ->formatStateUsing(fn ($state) => number_format($state, 2) . ' m³')
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('max_weight')
                    ->label('Max Weight')
                    ->formatStateUsing(fn ($state) => number_format($state, 0) . ' kg')
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('tare_weight')
                    ->label('Tare Weight')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0) . ' kg' : '-')
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('base_cost')
                    ->label('Base Cost')
                    ->formatStateUsing(function ($record) {
                        if (!$record->base_cost) return '-';
                        $currency = $record->currency?->code ?? 'USD';
                        return $currency . ' ' . number_format($record->base_cost / 100, 2);
                    })
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name', 'asc')
            ->emptyStateHeading('No container types')
            ->emptyStateDescription('Create your first container type to start managing shipment containers.')
            ->emptyStateIcon('heroicon-o-cube');
    }
}
