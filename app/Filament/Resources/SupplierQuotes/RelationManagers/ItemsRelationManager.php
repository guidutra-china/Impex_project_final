<?php

namespace App\Filament\Resources\SupplierQuotes\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Quote Items';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->columnSpan(2),

                TextInput::make('quantity')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->default(1)
                    ->columnSpan(1),

                TextInput::make('unit_price_before_commission')
                    ->label('Unit Price (cents)')
                    ->helperText('Price per unit in cents')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->columnSpan(1),

                Textarea::make('notes')
                    ->rows(2)
                    ->columnSpanFull(),
            ])
            ->columns(4);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product.name')
            ->columns([
                TextColumn::make('product.code')
                    ->label('Code')
                    ->searchable(),

                TextColumn::make('product.name')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('quantity')
                    ->alignCenter(),

                TextColumn::make('unit_price_before_commission')
                    ->label('Unit Price')
                    ->money(fn () => $this->getOwnerRecord()->currency?->code ?? 'USD', divideBy: 100),

                TextColumn::make('unit_price_after_commission')
                    ->label('Unit Price (w/ Commission)')
                    ->money(fn () => $this->getOwnerRecord()->currency?->code ?? 'USD', divideBy: 100)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total_price_before_commission')
                    ->label('Subtotal')
                    ->money(fn () => $this->getOwnerRecord()->currency?->code ?? 'USD', divideBy: 100),

                TextColumn::make('total_price_after_commission')
                    ->label('Total (w/ Commission)')
                    ->money(fn () => $this->getOwnerRecord()->currency?->code ?? 'USD', divideBy: 100),

                TextColumn::make('converted_price_cents')
                    ->label('Converted Price')
                    ->money(fn () => $this->getOwnerRecord()->order?->currency?->code ?? 'USD', divideBy: 100)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->after(function () {
                        // Recalculate commission after adding items
                        $this->getOwnerRecord()->calculateCommission();
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->after(function () {
                        // Recalculate commission after editing items
                        $this->getOwnerRecord()->calculateCommission();
                    }),
                DeleteAction::make()
                    ->after(function () {
                        // Recalculate commission after deleting items
                        $this->getOwnerRecord()->calculateCommission();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->after(function () {
                            // Recalculate commission after bulk delete
                            $this->getOwnerRecord()->calculateCommission();
                        }),
                ]),
            ]);
    }
}
