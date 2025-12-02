<?php

namespace App\Filament\Resources\ProformaInvoice\RelationManagers;

use App\Models\QuoteItem;
use App\Models\SupplierQuote;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Proforma Items';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('supplier_quote_id')
                    ->label('Source Supplier Quote')
                    ->options(function () {
                        return SupplierQuote::with('supplier', 'order')
                            ->get()
                            ->mapWithKeys(function ($quote) {
                                $label = sprintf(
                                    '%s - %s (RFQ: %s)',
                                    $quote->quote_number,
                                    $quote->supplier?->name ?? 'Unknown',
                                    $quote->order?->order_number ?? 'N/A'
                                );
                                return [$quote->id => $label];
                            });
                    })
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (Set $set) {
                        // Clear quote item when supplier quote changes
                        $set('quote_item_id', null);
                        $set('product_id', null);
                        $set('quantity', null);
                        $set('unit_price', null);
                        $set('commission_percent', null);
                        $set('commission_type', null);
                    })
                    ->helperText('Select supplier quote to load items from')
                    ->columnSpan(2),

                Select::make('quote_item_id')
                    ->label('Quote Item')
                    ->options(function (Get $get) {
                        $quoteId = $get('supplier_quote_id');
                        if (!$quoteId) {
                            return [];
                        }

                        return QuoteItem::where('supplier_quote_id', $quoteId)
                            ->with('product')
                            ->get()
                            ->mapWithKeys(function ($item) {
                                $label = sprintf(
                                    '%s - Qty: %d @ $%s',
                                    $item->product?->name ?? 'Unknown',
                                    $item->quantity,
                                    number_format($item->unit_price_after_commission / 100, 2)
                                );
                                return [$item->id => $label];
                            });
                    })
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                        if (!$state) {
                            return;
                        }

                        $quoteItem = QuoteItem::with('product')->find($state);
                        if (!$quoteItem) {
                            return;
                        }

                        // Auto-fill from quote item
                        $set('product_id', $quoteItem->product_id);
                        $set('quantity', $quoteItem->quantity);
                        $set('unit_price', $quoteItem->unit_price_after_commission / 100); // Convert from cents
                        $set('commission_percent', $quoteItem->commission_percent);
                        $set('commission_type', $quoteItem->commission_type);
                        $set('delivery_days', $quoteItem->delivery_days);
                        
                        // Calculate total
                        $total = ($quoteItem->quantity * $quoteItem->unit_price_after_commission) / 100;
                        $set('total', $total);
                        
                        // Calculate commission amount
                        if ($quoteItem->commission_type === 'embedded') {
                            $basePrice = $quoteItem->unit_price_before_commission / 100;
                            $commissionAmount = (($quoteItem->unit_price_after_commission - $quoteItem->unit_price_before_commission) * $quoteItem->quantity) / 100;
                            $set('commission_amount', $commissionAmount);
                        } else {
                            $commissionAmount = ($quoteItem->unit_price_before_commission * $quoteItem->quantity * $quoteItem->commission_percent / 100) / 100;
                            $set('commission_amount', $commissionAmount);
                        }
                    })
                    ->required()
                    ->disabled(fn (Get $get) => !$get('supplier_quote_id'))
                    ->helperText('Select item from the supplier quote')
                    ->columnSpan(2),

                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->disabled()
                    ->dehydrated()
                    ->columnSpan(2),

                TextInput::make('quantity')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->default(1)
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        $quantity = (float) $get('quantity');
                        $unitPrice = (float) $get('unit_price');
                        $total = $quantity * $unitPrice;
                        $set('total', $total);
                    })
                    ->columnSpan(1),

                TextInput::make('unit_price')
                    ->label('Unit Price (with commission)')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix('$')
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        $quantity = (float) $get('quantity');
                        $unitPrice = (float) $get('unit_price');
                        $total = $quantity * $unitPrice;
                        $set('total', $total);
                    })
                    ->columnSpan(1),

                TextInput::make('commission_percent')
                    ->label('Commission %')
                    ->numeric()
                    ->suffix('%')
                    ->disabled()
                    ->dehydrated()
                    ->columnSpan(1),

                Select::make('commission_type')
                    ->options([
                        'embedded' => 'Embedded',
                        'separate' => 'Separate',
                    ])
                    ->disabled()
                    ->dehydrated()
                    ->columnSpan(1),

                TextInput::make('commission_amount')
                    ->label('Commission Amount')
                    ->numeric()
                    ->prefix('$')
                    ->disabled()
                    ->dehydrated()
                    ->columnSpan(1),

                TextInput::make('total')
                    ->label('Total')
                    ->numeric()
                    ->disabled()
                    ->dehydrated()
                    ->prefix('$')
                    ->columnSpan(1),

                TextInput::make('delivery_days')
                    ->label('Delivery (days)')
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

                TextColumn::make('unit_price')
                    ->label('Unit Price')
                    ->money(fn () => $this->getOwnerRecord()->currency?->code ?? 'USD'),

                TextColumn::make('commission_percent')
                    ->label('Comm %')
                    ->suffix('%')
                    ->alignCenter()
                    ->toggleable(),

                TextColumn::make('commission_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'embedded' => 'success',
                        'separate' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->toggleable(),

                TextColumn::make('total')
                    ->label('Total')
                    ->money(fn () => $this->getOwnerRecord()->currency?->code ?? 'USD')
                    ->weight('bold'),

                TextColumn::make('supplierQuote.quote_number')
                    ->label('Source Quote')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
