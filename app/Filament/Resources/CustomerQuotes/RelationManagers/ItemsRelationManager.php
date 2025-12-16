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

    protected static ?string $recordTitleAttribute = 'display_name';

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
                TextColumn::make('display_name')
                    ->label('Option')
                    ->badge()
                    ->color('primary')
                    ->size('lg')
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('supplierQuote.supplier.name')
                    ->label('Supplier (Internal Only)')
                    ->toggleable()
                    ->toggledHiddenByDefault()
                    ->description('Hidden from customer')
                    ->color('gray'),

                TextColumn::make('price_before_commission')
                    ->label('Base Price')
                    ->money(fn ($record) => $record->customerQuote->order->currency->code ?? 'USD', divideBy: 100)
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('commission_amount')
                    ->label('Commission')
                    ->money(fn ($record) => $record->customerQuote->order->currency->code ?? 'USD', divideBy: 100)
                    ->sortable()
                    ->toggleable()
                    ->color('warning'),

                TextColumn::make('price_after_commission')
                    ->label('Total Price')
                    ->money(fn ($record) => $record->customerQuote->order->currency->code ?? 'USD', divideBy: 100)
                    ->sortable()
                    ->weight('bold')
                    ->size('lg')
                    ->color('success'),

                TextColumn::make('delivery_time')
                    ->label('Delivery Time')
                    ->placeholder('Not specified')
                    ->icon('heroicon-o-clock')
                    ->sortable(),

                TextColumn::make('moq')
                    ->label('MOQ')
                    ->numeric()
                    ->placeholder('N/A')
                    ->toggleable()
                    ->sortable(),

                TextColumn::make('highlights')
                    ->label('Highlights')
                    ->wrap()
                    ->lineClamp(2)
                    ->placeholder('No highlights')
                    ->toggleable(),

                TextColumn::make('is_selected_by_customer')
                    ->label('Customer Selection')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                    ->formatStateUsing(fn (bool $state): string => $state ? '✓ Selected' : 'Not selected')
                    ->sortable(),

                TextColumn::make('display_order')
                    ->label('Order')
                    ->numeric()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Items are created via CustomerQuoteService, not manually
            ])
            ->actions([
                // View supplier quote details could be added here
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('display_order', 'asc')
            ->emptyStateHeading('No quote options')
            ->emptyStateDescription('Generate a customer quote from supplier quotes to see options here.')
            ->emptyStateIcon('heroicon-o-inbox');
    }
}
