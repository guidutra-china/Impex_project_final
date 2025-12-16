<?php

namespace App\Filament\Resources\CustomerQuotes\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Quote Options';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Items are managed through the service, not directly editable
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('option_label')
                    ->label('Option')
                    ->badge()
                    ->color('info')
                    ->weight('bold'),

                TextColumn::make('supplierQuote.supplier.name')
                    ->label('Supplier (Internal)')
                    ->toggleable()
                    ->placeholder('N/A'),

                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('unit_price')
                    ->label('Unit Price')
                    ->money(fn ($record) => $record->customerQuote->order->currency->code ?? 'USD', divideBy: 100)
                    ->sortable(),

                TextColumn::make('total_price')
                    ->label('Total Price')
                    ->money(fn ($record) => $record->customerQuote->order->currency->code ?? 'USD', divideBy: 100)
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('lead_time_days')
                    ->label('Lead Time')
                    ->suffix(' days')
                    ->sortable()
                    ->placeholder('N/A'),

                TextColumn::make('is_selected')
                    ->label('Selected')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Items are created through the service
            ])
            ->actions([
                // Items are not directly editable
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('option_label', 'asc')
            ->emptyStateHeading('No quote options')
            ->emptyStateDescription('Quote options are automatically generated from supplier quotes.');
    }
}
