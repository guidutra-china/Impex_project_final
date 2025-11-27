<?php

namespace App\Filament\Resources\PurchaseOrders\Schemas;

use App\Models\Currency;
use App\Models\Product;
use App\Models\PurchaseOrder;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class PurchaseOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make()
                            ->schema(static::getDetailsComponents())
                            ->columns(2),

                        Section::make('Purchase Order Items')
                            ->schema([
                                static::getItemsRepeater(),
                            ]),

                        Section::make('INCOTERMS & Delivery')
                            ->schema(static::getIncotermsComponents())
                            ->columns(2)
                            ->collapsible(),

                        Section::make('Payment Terms')
                            ->schema(static::getPaymentComponents())
                            ->columns(2)
                            ->collapsible(),

                        Section::make('Additional Costs')
                            ->schema(static::getCostsComponents())
                            ->columns(2)
                            ->collapsible(),

                        Section::make('Totals')
                            ->schema(static::getTotalsComponents())
                            ->columns(3)
                            ->collapsible(),

                        Section::make('Notes & Terms')
                            ->schema(static::getNotesComponents())
                            ->columns(1)
                            ->collapsible()
                            ->collapsed(),
                    ])
                    ->columnSpan(['lg' => fn (?PurchaseOrder $record) => $record === null ? 3 : 2]),

                Section::make()
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Created')
                            ->state(fn (PurchaseOrder $record): ?string => $record->created_at?->diffForHumans()),

                        TextEntry::make('updated_at')
                            ->label('Last modified')
                            ->state(fn (PurchaseOrder $record): ?string => $record->updated_at?->diffForHumans()),

                        TextEntry::make('sent_at')
                            ->label('Sent to supplier')
                            ->state(fn (PurchaseOrder $record): ?string => $record->sent_at?->diffForHumans() ?? 'Not sent'),

                        TextEntry::make('confirmed_at')
                            ->label('Confirmed')
                            ->state(fn (PurchaseOrder $record): ?string => $record->confirmed_at?->diffForHumans() ?? 'Not confirmed'),
                    ])
                    ->columnSpan(['lg' => 1])
                    ->hidden(fn (?PurchaseOrder $record) => $record === null),
            ])
            ->columns(3);
    }

    /**
     * Get basic details components
     */
    public static function getDetailsComponents(): array
    {
        return [
            TextInput::make('po_number')
                ->label('PO Number')
                ->default(fn () => 'PO-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT))
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(50),

            TextInput::make('revision_number')
                ->label('Revision')
                ->numeric()
                ->default(1)
                ->required()
                ->minValue(1),

            DatePicker::make('po_date')
                ->label('PO Date')
                ->default(now())
                ->required()
                ->native(false),

            Select::make('status')
                ->options([
                    'draft' => 'Draft',
                    'sent' => 'Sent',
                    'confirmed' => 'Confirmed',
                    'received' => 'Received',
                    'paid' => 'Paid',
                    'cancelled' => 'Cancelled',
                ])
                ->default('draft')
                ->required()
                ->native(false),

            Select::make('order_id')
                ->label('RFQ')
                ->relationship('order', 'order_number')
                ->searchable()
                ->preload()
                ->native(false),

            Select::make('supplier_quote_id')
                ->label('Supplier Quote')
                ->relationship(
                    name: 'supplierQuote',
                    titleAttribute: 'quote_number',
                    modifyQueryUsing: fn ($query) => $query->with(['supplier', 'currency', 'items.product'])
                )
                ->getOptionLabelFromRecordUsing(fn ($record) => 
                    ($record->supplier?->name ?? 'Unknown') . ' - ' . $record->quote_number
                )
                ->searchable(['quote_number', 'supplier.name'])
                ->preload()
                ->native(false)
                ->live()
                ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                    if (!$state) return;
                    
                    $quote = \App\Models\SupplierQuote::with(['supplier', 'currency', 'items.product'])
                        ->find($state);
                    
                    if (!$quote) return;
                    
                    // Fill supplier and currency
                    $set('supplier_id', $quote->supplier_id);
                    $set('currency_id', $quote->currency_id);
                    $set('exchange_rate', $quote->locked_exchange_rate ?? $quote->currency?->exchange_rate ?? 1);
                    
                    // Fill items from quote
                    $items = [];
                    foreach ($quote->items as $quoteItem) {
                        // QuoteItem values are already in cents, convert to decimal for display
                        $unitCost = $quoteItem->unit_price_before_commission; // In cents
                        $totalCost = $quoteItem->total_price_before_commission; // In cents
                        
                        $items[] = [
                            'product_id' => $quoteItem->product_id,
                            'product_name' => $quoteItem->product?->name ?? '',
                            'product_sku' => $quoteItem->product?->sku ?? '',
                            'quantity' => $quoteItem->quantity,
                            'unit_cost' => $unitCost / 100, // Convert cents to decimal for form display
                            'total_cost' => $totalCost / 100, // Convert cents to decimal for form display
                            'notes' => $quoteItem->supplier_notes ?? '',
                        ];
                    }
                    $set('items', $items);
                }),

            Select::make('supplier_id')
                ->label('Supplier')
                ->relationship('supplier', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->native(false),

            Select::make('currency_id')
                ->label('PO Currency')
                ->relationship('currency', 'code')
                ->searchable()
                ->preload()
                ->required()
                ->native(false)
                ->live()
                ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                    if ($state) {
                        $baseCurrencyId = $get('base_currency_id');
                        
                        // If same currency, set rate to 1
                        if ($state == $baseCurrencyId) {
                            $set('exchange_rate', 1);
                        } else {
                            $currency = Currency::find($state);
                            if ($currency && $currency->exchange_rate) {
                                $set('exchange_rate', $currency->exchange_rate);
                            }
                        }
                    }
                }),

            TextInput::make('exchange_rate')
                ->label('Exchange Rate')
                ->numeric()
                ->required()
                ->minValue(0.000001)
                ->step('any')
                ->default(1)
                ->helperText('Rate locked at PO creation'),

            Select::make('base_currency_id')
                ->label('Base Currency')
                ->relationship('baseCurrency', 'code')
                ->searchable()
                ->preload()
                ->required()
                ->native(false)
                ->default(fn () => Currency::where('code', 'USD')->first()?->id),
        ];
    }

    /**
     * Get items repeater
     */
    public static function getItemsRepeater(): Repeater
    {
        return Repeater::make('items')
            ->relationship('items')
            ->schema([
                Select::make('product_id')
                    ->label('Product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                        if ($state) {
                            $product = Product::find($state);
                            if ($product) {
                                $set('product_name', $product->name);
                                $set('product_sku', $product->sku);
                                $set('unit_cost', $product->unit_price ?? 0);
                            }
                        }
                    })
                    ->columnSpan(3),
                
                TextInput::make('quantity')
                    ->label('Qty')
                    ->numeric()
                    ->required()
                    ->minValue(0.01)
                    ->default(1)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Get $get, Set $set) => 
                        $set('total_cost', $get('quantity') * $get('unit_cost'))
                    )
                    ->columnSpan(1),
                
                TextInput::make('unit_cost')
                    ->label('Unit Cost')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->step(0.01)
                    ->default(0)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Get $get, Set $set) => 
                        $set('total_cost', $get('quantity') * $get('unit_cost'))
                    )
                    ->columnSpan(2),
                
                // Hidden fields for product snapshot
                TextInput::make('product_name')
                    ->hidden()
                    ->dehydrated()
                    ->default(''),
                
                TextInput::make('product_sku')
                    ->hidden()
                    ->dehydrated()
                    ->default(''),
                
                TextInput::make('total_cost')
                    ->label('Total Cost')
                    ->numeric()
                    ->disabled()
                    ->dehydrated()
                    ->columnSpan(2),
                
                Textarea::make('notes')
                    ->label('Notes')
                    ->rows(1)
                    ->columnSpanFull(),
            ])
            ->columns(8)
            ->defaultItems(1)
            ->reorderable()
            ->collapsible()
            ->itemLabel(fn (array $state): ?string => 
                $state['product_name'] ?? 'New Item'
            )
            ->deleteAction(
                fn ($action) => $action->after(fn (Get $get, Set $set) => 
                    self::updateTotals($get, $set)
                )
            )
            ->columnSpanFull();
    }

    /**
     * Get INCOTERMS components
     */
    public static function getIncotermsComponents(): array
    {
        return [
            Select::make('incoterm')
                ->label('INCOTERM')
                ->options([
                    'EXW' => 'EXW - Ex Works',
                    'FCA' => 'FCA - Free Carrier',
                    'FAS' => 'FAS - Free Alongside Ship',
                    'FOB' => 'FOB - Free On Board',
                    'CFR' => 'CFR - Cost and Freight',
                    'CIF' => 'CIF - Cost, Insurance and Freight',
                    'CPT' => 'CPT - Carriage Paid To',
                    'CIP' => 'CIP - Carriage and Insurance Paid To',
                    'DAP' => 'DAP - Delivered At Place',
                    'DPU' => 'DPU - Delivered at Place Unloaded',
                    'DDP' => 'DDP - Delivered Duty Paid',
                ])
                ->searchable()
                ->native(false)
                ->helperText('INCOTERMS 2020'),

            TextInput::make('incoterm_location')
                ->label('Location')
                ->placeholder('e.g., Shanghai Port')
                ->maxLength(255),

            Toggle::make('shipping_included_in_price')
                ->label('Shipping included in item prices')
                ->default(false)
                ->inline(false),

            Toggle::make('insurance_included_in_price')
                ->label('Insurance included in item prices')
                ->default(false)
                ->inline(false),

            DatePicker::make('expected_delivery_date')
                ->label('Expected Delivery')
                ->native(false),

            DatePicker::make('actual_delivery_date')
                ->label('Actual Delivery')
                ->native(false),

            Textarea::make('delivery_address')
                ->label('Delivery Address')
                ->rows(3)
                ->columnSpanFull(),
        ];
    }

    /**
     * Get payment components
     */
    public static function getPaymentComponents(): array
    {
        return [
            Select::make('payment_term_id')
                ->label('Payment Term')
                ->relationship('paymentTerm', 'name')
                ->searchable()
                ->preload()
                ->native(false),

            Textarea::make('payment_terms_text')
                ->label('Additional Payment Terms')
                ->rows(3)
                ->placeholder('Any special payment conditions...')
                ->columnSpanFull(),
        ];
    }

    /**
     * Get costs components
     */
    public static function getCostsComponents(): array
    {
        return [
            TextInput::make('shipping_cost')
                ->label('Shipping Cost')
                ->numeric()
                ->default(0)
                ->minValue(0)
                ->step(0.01)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Get $get, Set $set) => self::updateTotals($get, $set)),

            TextInput::make('insurance_cost')
                ->label('Insurance Cost')
                ->numeric()
                ->default(0)
                ->minValue(0)
                ->step(0.01)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Get $get, Set $set) => self::updateTotals($get, $set)),

            TextInput::make('other_costs')
                ->label('Other Costs')
                ->numeric()
                ->default(0)
                ->minValue(0)
                ->step(0.01)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Get $get, Set $set) => self::updateTotals($get, $set)),

            TextInput::make('discount')
                ->label('Discount')
                ->numeric()
                ->default(0)
                ->minValue(0)
                ->step(0.01)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Get $get, Set $set) => self::updateTotals($get, $set)),

            TextInput::make('tax')
                ->label('Tax')
                ->numeric()
                ->default(0)
                ->minValue(0)
                ->step(0.01)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Get $get, Set $set) => self::updateTotals($get, $set)),
        ];
    }

    /**
     * Get totals components
     */
    public static function getTotalsComponents(): array
    {
        return [
            TextInput::make('subtotal')
                ->label('Subtotal')
                ->formatStateUsing(fn ($state) => $state ? number_format($state, 2, '.', ',') : '0.00')
                ->prefix('$')
                ->disabled()
                ->dehydrated(false) // Don't save - calculated by recalculateTotals()
                ->default(0),

            TextInput::make('total')
                ->label('Total')
                ->formatStateUsing(fn ($state) => $state ? number_format($state, 2, '.', ',') : '0.00')
                ->prefix('$')
                ->disabled()
                ->dehydrated(false) // Don't save - calculated by recalculateTotals()
                ->default(0),

            TextInput::make('total_base_currency')
                ->label('Total (Base Currency)')
                ->formatStateUsing(fn ($state) => $state ? number_format($state, 2, '.', ',') : '0.00')
                ->prefix('$')
                ->disabled()
                ->dehydrated(false) // Don't save - calculated by recalculateTotals()
                ->default(0),
        ];
    }

    /**
     * Get notes components
     */
    public static function getNotesComponents(): array
    {
        return [
            Textarea::make('notes')
                ->label('Internal Notes')
                ->rows(3)
                ->columnSpanFull(),

            Textarea::make('terms_and_conditions')
                ->label('Terms and Conditions')
                ->rows(4)
                ->columnSpanFull(),
        ];
    }

    /**
     * Update all totals based on items and costs
     */
    protected static function updateTotals(Get $get, Set $set): void
    {
        // Calculate subtotal from items
        $items = collect($get('items') ?? []);
        $subtotal = $items->sum('total_cost');
        
        // Get costs
        $shipping = floatval($get('shipping_cost') ?? 0);
        $insurance = floatval($get('insurance_cost') ?? 0);
        $other = floatval($get('other_costs') ?? 0);
        $discount = floatval($get('discount') ?? 0);
        $tax = floatval($get('tax') ?? 0);
        
        // Calculate total
        $total = $subtotal + $shipping + $insurance + $other - $discount + $tax;
        
        // Calculate total in base currency
        $exchangeRate = floatval($get('exchange_rate') ?? 1);
        $totalBaseCurrency = $total * $exchangeRate;
        
        // Set values
        $set('subtotal', round($subtotal, 2));
        $set('total', round($total, 2));
        $set('total_base_currency', round($totalBaseCurrency, 2));
    }
}
