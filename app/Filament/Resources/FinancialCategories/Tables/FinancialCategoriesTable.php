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
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('type')
                    ->label('Tipo')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'expense' => 'Despesa',
                        'revenue' => 'Receita',
                        'exchange_variation' => 'Variação Cambial',
                        default => $state,
                    })
                    ->colors([
                        'danger' => 'expense',
                        'success' => 'revenue',
                        'warning' => 'exchange_variation',
                    ]),

                TextColumn::make('parent.name')
                    ->label('Categoria Pai')
                    ->searchable()
                    ->toggleable(),

                IconColumn::make('is_system')
                    ->label('Sistema')
                    ->boolean()
                    ->toggleable(),

                TextColumn::make('transactions_count')
                    ->label('Transações')
                    ->counts('transactions')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'expense' => 'Despesa',
                        'revenue' => 'Receita',
                        'exchange_variation' => 'Variação Cambial',
                    ]),

                TernaryFilter::make('is_system')
                    ->label('Sistema')
                    ->placeholder('Todas')
                    ->trueLabel('Apenas do Sistema')
                    ->falseLabel('Apenas Customizadas'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn ($record) => !$record->is_system),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
