<?php

namespace App\Filament\Resources\SupplierPerformanceMetrics\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SupplierPerformanceMetricsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('supplier.name')
                    ->searchable(),
                TextColumn::make('period_year')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('period_month')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_orders')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('on_time_deliveries')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('late_deliveries')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('average_delay_days')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_inspections')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('passed_inspections')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('failed_inspections')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('quality_score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_purchase_value')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_orders_value')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('average_order_value')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('response_time_hours')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('communication_score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('overall_score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('rating')
                    ->badge(),
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
