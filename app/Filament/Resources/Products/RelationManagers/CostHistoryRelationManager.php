<?php

namespace App\Filament\Resources\SupplierQuotes\Products\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use BackedEnum;

class CostHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'costHistory';

    protected static ?string $title = 'Cost History';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClockRotateLeft;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cost_field_name')
                    ->label('Cost Field')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('old_value')
                    ->label('Old Value')
                    ->money('USD', divideBy: 100)
                    ->sortable(),

                TextColumn::make('new_value')
                    ->label('New Value')
                    ->money('USD', divideBy: 100)
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('difference')
                    ->label('Change')
                    ->money('USD', divideBy: 100)
                    ->sortable()
                    ->color(fn ($record) => $record->isIncrease() ? 'danger' : 'success')
                    ->icon(fn ($record) => $record->isIncrease() ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                    ->iconPosition('after'),

                TextColumn::make('percentage_change')
                    ->label('Change %')
                    ->formatStateUsing(fn ($state) => number_format($state, 2) . '%')
                    ->sortable()
                    ->color(fn ($record) => $record->isIncrease() ? 'danger' : 'success'),

                TextColumn::make('change_reason')
                    ->label('Reason')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->change_reason)
                    ->default('—')
                    ->toggleable(),

                TextColumn::make('changedBy.name')
                    ->label('Changed By')
                    ->default('System')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable()
                    ->description(fn ($record) => $record->created_at->diffForHumans()),
            ])
            ->filters([
                SelectFilter::make('cost_field')
                    ->options([
                        'unit_cost' => 'Unit Cost',
                        'labor_cost_per_unit' => 'Labor Cost',
                        'overhead_cost_per_unit' => 'Overhead Cost',
                        'total_cost_per_unit' => 'Total Cost',
                        'bom_material_cost' => 'BOM Material Cost',
                        'direct_labor_cost' => 'Direct Labor Cost',
                        'direct_overhead_cost' => 'Direct Overhead Cost',
                        'total_manufacturing_cost' => 'Total Manufacturing Cost',
                    ])
                    ->label('Cost Field'),

                SelectFilter::make('change_type')
                    ->options([
                        'increase' => 'Increases',
                        'decrease' => 'Decreases',
                    ])
                    ->query(function ($query, $state) {
                        if ($state['value'] === 'increase') {
                            return $query->increases();
                        } elseif ($state['value'] === 'decrease') {
                            return $query->decreases();
                        }
                    })
                    ->label('Change Type'),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No cost changes recorded')
            ->emptyStateDescription('Cost changes will be tracked automatically')
            ->emptyStateIcon('heroicon-o-clock');
    }
}
