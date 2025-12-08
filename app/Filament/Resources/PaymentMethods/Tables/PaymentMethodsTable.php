<?php

namespace App\Filament\Resources\PaymentMethods\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PaymentMethodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('fields.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label(__('fields.type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'bank_transfer' => 'Bank Transfer',
                        'wire_transfer' => 'Wire Transfer',
                        'paypal' => 'PayPal',
                        'credit_card' => 'Credit Card',
                        'debit_card' => 'Debit Card',
                        'check' => 'Check',
                        'cash' => 'Cash',
                        'wise' => 'Wise',
                        'cryptocurrency' => 'Crypto',
                        'other' => 'Other',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'bank_transfer', 'wire_transfer' => 'info',
                        'paypal', 'wise' => 'warning',
                        'credit_card', 'debit_card' => 'success',
                        'cash' => 'gray',
                        'cryptocurrency' => 'purple',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('bankAccount.account_name')
                    ->label('Bank Account')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('fee_type')
                    ->label('Fee Type')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'none' => 'No Fee',
                        'fixed' => 'Fixed',
                        'percentage' => 'Percentage',
                        'fixed_plus_percentage' => 'Fixed + %',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => $state === 'none' ? 'success' : 'warning'),

                TextColumn::make('fixed_fee')
                    ->label('Fixed Fee')
                    ->formatStateUsing(fn ($state) => $state ? '$' . number_format($state / 100, 2) : '—')
                    ->visible(fn ($record) => in_array($record->fee_type ?? 'none', ['fixed', 'fixed_plus_percentage'])),

                TextColumn::make('percentage_fee')
                    ->label('% Fee')
                    ->formatStateUsing(fn ($state) => $state ? $state . '%' : '—')
                    ->visible(fn ($record) => in_array($record->fee_type ?? 'none', ['percentage', 'fixed_plus_percentage'])),

                TextColumn::make('processing_time')
                    ->label('Processing')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'immediate' => 'Immediate',
                        'same_day' => 'Same Day',
                        '1_3_days' => '1-3 Days',
                        '3_5_days' => '3-5 Days',
                        '5_7_days' => '5-7 Days',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'immediate' => 'success',
                        'same_day' => 'info',
                        default => 'gray',
                    }),

                IconColumn::make('is_active')
                    ->label(__('common.active'))
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('fields.type'))
                    ->options([
                        'bank_transfer' => 'Bank Transfer',
                        'wire_transfer' => 'Wire Transfer',
                        'paypal' => 'PayPal',
                        'credit_card' => 'Credit Card',
                        'debit_card' => 'Debit Card',
                        'check' => 'Check',
                        'cash' => 'Cash',
                        'wise' => 'Wise',
                        'cryptocurrency' => 'Cryptocurrency',
                        'other' => 'Other',
                    ]),

                SelectFilter::make('is_active')
                    ->label(__('fields.status'))
                    ->options([
                        true => 'Active',
                        false => 'Inactive',
                    ]),

                SelectFilter::make('fee_type')
                    ->label('Fee Type')
                    ->options([
                        'none' => 'No Fee',
                        'fixed' => 'Fixed',
                        'percentage' => 'Percentage',
                        'fixed_plus_percentage' => 'Fixed + Percentage',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name', 'asc');
    }
}
