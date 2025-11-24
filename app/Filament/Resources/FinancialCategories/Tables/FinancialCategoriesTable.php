<?php

namespace App\Filament\Resources\FinancialCategories\Tables;

use Filament\Tables;
use Filament\Tables\Table;

class FinancialCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('type')
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

                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Categoria Pai')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_system')
                    ->label('Sistema')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('transactions_count')
                    ->label('Transações')
                    ->counts('transactions')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'expense' => 'Despesa',
                        'revenue' => 'Receita',
                        'exchange_variation' => 'Variação Cambial',
                    ]),

                Tables\Filters\TernaryFilter::make('is_system')
                    ->label('Sistema')
                    ->placeholder('Todas')
                    ->trueLabel('Apenas do Sistema')
                    ->falseLabel('Apenas Customizadas'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => !$record->is_system),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
