<?php

namespace App\Filament\Resources\Orders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->searchable(),
                TextColumn::make('client_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('order_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('paymentTerm.name')
                    ->label('Payment Term')
                    ->sortable(),
                TextColumn::make('total_amount_cents')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('currency_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('exchange_rate_to_usd')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_amount_usd_cents')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('invoice_number')
                    ->searchable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('payment_status')
                    ->searchable(),
                TextColumn::make('shipping_company')
                    ->searchable(),
                TextColumn::make('shipping_document')
                    ->searchable(),
                TextColumn::make('shipping_value_cents')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('shipping_value_usd_cents')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('etd')
                    ->date()
                    ->sortable(),
                TextColumn::make('eta')
                    ->date()
                    ->sortable(),
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
