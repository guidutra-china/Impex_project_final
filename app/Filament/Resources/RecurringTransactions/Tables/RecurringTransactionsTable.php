<?php

namespace App\Filament\Resources\RecurringTransactions\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
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
                    ->label(__('fields.name'))
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('type')
                    ->label(__('fields.type'))
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
                    ->label(__('fields.amount'))
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
                    ->label(__('common.active'))
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('fields.type'))
                    ->options([
                        'payable' => 'Payable',
                        'receivable' => 'Receivable',
                    ]),

                TernaryFilter::make('is_active')
                    ->label(__('common.active')),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->defaultSort('next_due_date');
    }
}
