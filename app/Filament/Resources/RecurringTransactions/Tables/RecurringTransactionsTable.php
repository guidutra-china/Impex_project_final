<?php

namespace App\Filament\Resources\RecurringTransactions\Tables;

use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class RecurringTransactionsTable
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
                        'payable' => 'Payable',
                        'receivable' => 'Receivable',
                        default => $state
                    })
                    ->colors([
                        'danger' => 'payable',
                        'success' => 'receivable',
                    ]),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->money(fn ($record) => $record->currency->code, divideBy: 100)
                    ->sortable(),

                TextColumn::make('frequency')
                    ->label('Frequency')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'daily' => 'Daily',
                        'weekly' => 'Weekly',
                        'monthly' => 'Monthly',
                        'quarterly' => 'Quarterly',
                        'yearly' => 'Yearly',
                        default => $state
                    }),

                TextColumn::make('next_due_date')
                    ->label('Next Due Date')
                    ->date('Y-m-d')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'payable' => 'Payable',
                        'receivable' => 'Receivable',
                    ]),

                TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->defaultSort('next_due_date');
    }
}
