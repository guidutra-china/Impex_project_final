<?php

namespace App\Filament\Resources\FinancialCategories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class FinancialCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'expense' => 'Expense',
                        'revenue' => 'Revenue',
                        'exchange_variation' => 'Exchange Variation',
                        default => $state,
                    })
                    ->colors([
                        'danger' => 'expense',
                        'success' => 'revenue',
                        'warning' => 'exchange_variation',
                    ]),

                TextColumn::make('parent.name')
                    ->label('Parent Category')
                    ->searchable()
                    ->toggleable(),

                IconColumn::make('is_system')
                    ->label('System')
                    ->boolean()
                    ->toggleable(),

                TextColumn::make('transactions_count')
                    ->label('Transactions')
                    ->counts('transactions')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'expense' => 'Expense',
                        'revenue' => 'Revenue',
                        'exchange_variation' => 'Exchange Variation',
                    ]),

                TernaryFilter::make('is_system')
                    ->label('System')
                    ->placeholder('All')
                    ->trueLabel('System Only')
                    ->falseLabel('Custom Only'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn ($record) => !$record->is_system),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
