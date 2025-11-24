<?php

namespace App\Filament\Resources\FinancialTransactions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FinancialTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transaction_number')
                    ->label('Número')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                BadgeColumn::make('type')
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

                TextColumn::make('description')
                    ->label('Descrição')
                    ->searchable()
                    ->limit(50),

                TextColumn::make('amount')
                    ->label('Valor')
                    ->money(fn ($record) => $record->currency->code, divideBy: 100)
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable(),

                BadgeColumn::make('status')
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

                TextColumn::make('category.name')
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
                EditAction::make()
                    ->visible(fn ($record) => $record->status !== 'paid'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('due_date', 'asc');
    }
}
