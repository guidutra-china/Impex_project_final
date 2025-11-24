<?php

namespace App\Filament\Resources\FinancialTransactions\Tables;

use Filament\Tables;
use Filament\Tables\Table;

class FinancialTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_number')
                    ->label('Número')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tipo')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'payable' => 'A Pagar',
                        'receivable' => 'A Receber',
                        default => $state,
                    })
                    ->colors([
                        'danger' => 'payable',
                        'success' => 'receivable',
                    ]),

                Tables\Columns\TextColumn::make('description')
                    ->label('Descrição')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Valor')
                    ->money(fn ($record) => $record->currency->code, divideBy: 100)
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendente',
                        'partially_paid' => 'Parcial',
                        'paid' => 'Pago',
                        'overdue' => 'Vencido',
                        'cancelled' => 'Cancelado',
                        default => $state,
                    })
                    ->colors([
                        'secondary' => 'pending',
                        'warning' => 'partially_paid',
                        'success' => 'paid',
                        'danger' => 'overdue',
                        'gray' => 'cancelled',
                    ]),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Categoria')
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'payable' => 'A Pagar',
                        'receivable' => 'A Receber',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pendente',
                        'paid' => 'Pago',
                        'overdue' => 'Vencido',
                    ]),

                Tables\Filters\SelectFilter::make('financial_category_id')
                    ->label('Categoria')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->status !== 'paid'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('due_date', 'asc');
    }
}
