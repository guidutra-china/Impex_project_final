<?php

namespace App\Filament\Resources\SupplierQuotes\RelationManagers;

use App\Models\OrderItem;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Quote Items';

    protected static ?string $recordTitleAttribute = 'product.name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('order_item_id')
                    ->label('RFQ Item')
                    ->options(function () {
                        $supplierQuote = $this->getOwnerRecord();
                        $order = $supplierQuote->order;
                        
                        return $order->items()
                            ->with('product')
                            ->get()
                            ->mapWithKeys(function ($item) {
                                return [$item->id => $item->product->name . ' (Qty: ' . $item->quantity . ')'];
                            });
                    })
                    ->required()
                    ->searchable()
                    ->preload()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $orderItem = OrderItem::find($state);
                            if ($orderItem) {
                                $set('product_id', $orderItem->product_id);
                                $set('quantity', $orderItem->quantity);
                            }
                        }
                    })
                    ->helperText('Select which RFQ item this quote is for')
                    ->columnSpan(2),

                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->disabled()
                    ->dehydrated()
                    ->label(__('fields.product'))
                    ->columnSpan(1),

                TextInput::make('quantity')
                    ->label('Quoted Quantity')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->default(1)
                    ->columnSpan(1),

                TextInput::make('unit_price_before_commission')
                    ->label(__('fields.unit_price'))
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->step(0.01)
                    ->minValue(0)
                    ->helperText('Price will be stored in cents')
                    ->dehydrateStateUsing(fn ($state) => $state ? (int) ($state * 100) : null)
                    ->formatStateUsing(fn ($state) => $state ? $state / 100 : null)
                    ->columnSpan(1),

                TextInput::make('delivery_days')
                    ->label('Delivery Time (days)')
                    ->numeric()
                    ->minValue(0)
                    ->helperText('Number of days for delivery')
                    ->columnSpan(1),

                TextInput::make('supplier_part_number')
                    ->label('Supplier Part Number')
                    ->maxLength(255)
                    ->columnSpan(1),

                Textarea::make('supplier_notes')
                    ->label('Supplier Notes')
                    ->rows(3)
                    ->columnSpan(2),

                Textarea::make('notes')
                    ->label('Internal Notes')
                    ->rows(3)
                    ->columnSpan(2),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('orderItem.product.name')
                    ->label(__('fields.product'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('quantity')
                    ->label(__('fields.qty'))
                    ->sortable(),

                TextColumn::make('unit_price_before_dollars')
                    ->label(__('fields.unit_price'))
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('total_price_before_dollars')
                    ->label(__('fields.total'))
                    ->money('USD')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('delivery_days')
                    ->label('Delivery')
                    ->suffix(' days')
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('supplier_part_number')
                    ->label('Part #')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('supplier_notes')
                    ->label(__('fields.notes'))
                    ->limit(50)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add Item')
                    ->icon('heroicon-o-plus'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id');
    }
}
