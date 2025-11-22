<?php

namespace App\Filament\Resources\PurchaseOrders\Schemas;

use App\Models\Currency;
use App\Models\Product;
use Filament\Schemas\Components\DatePicker;
use Filament\Schemas\Components\DateTimePicker;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Placeholder;
use Filament\Schemas\Components\Repeater;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Select;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Components\Textarea;
use Filament\Schemas\Components\Toggle;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Schemas\Schema;

class PurchaseOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                
                // ==========================================
                // SECTION 1: BASIC INFORMATION
                // ==========================================
                Section::make('Basic Information')
                    ->description('Purchase Order identification and dates')
                    ->schema([
                        Group::make()
                            ->schema([
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
                            ])
                            ->columns(2),
                        
                        Group::make()
                            ->schema([
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
                            ])
                            ->columns(2),
                    ])
                    ->columns(1)
                    ->collapsible(),
                
                // ==========================================
                // SECTION 2: REFERENCE (RFQ/QUOTE)
                // ==========================================
                Section::make('Reference')
                    ->description('Link to RFQ and Supplier Quote (optional)')
                    ->schema([
                        Group::make()
                            ->schema([
                                Select::make('order_id')
                                    ->label('RFQ')
                                    ->relationship('order', 'rfq_number')
                                    ->searchable()
                                    ->preload()
                                    ->native(false),
                                
                                Select::make('supplier_quote_id')
                                    ->label('Supplier Quote')
                                    ->relationship('supplierQuote', 'id')
                                    ->searchable()
                                    ->preload()
                                    ->native(false),
                            ])
                            ->columns(2),
                    ])
                    ->columns(1)
                    ->collapsible(),
                
                // ==========================================
                // SECTION 3: SUPPLIER & CURRENCY
                // ==========================================
                Section::make('Supplier & Currency')
                    ->description('Select supplier and currency details')
                    ->schema([
                        Select::make('supplier_id')
                            ->label('Supplier')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false)
                            ->columnSpan(2),
                        
                        Group::make()
                            ->schema([
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
                                            $currency = Currency::find($state);
                                            if ($currency && $currency->exchange_rate) {
                                                $set('exchange_rate', $currency->exchange_rate);
                                            }
                                        }
                                    }),
                                
                                TextInput::make('exchange_rate')
                                    ->label('Exchange Rate')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0.000001)
                                    ->step(0.0001)
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
                            ])
                            ->columns(3),
                    ])
                    ->columns(1)
                    ->collapsible(),
                
                // ==========================================
                // SECTION 4: INCOTERMS & DELIVERY
                // ==========================================
                Section::make('INCOTERMS & Delivery')
                    ->description('Shipping terms and delivery details')
                    ->schema([
                        Group::make()
                            ->schema([
                                Select::make('incoterm')
                                    ->label('INCOTERM')
                                    ->options([
                                        'EXW' => 'EXW - Ex Works',
                                        'FCA' => 'FCA - Free Carrier',
                                        'CPT' => 'CPT - Carriage Paid To',
                                        'CIP' => 'CIP - Carriage and Insurance Paid To',
                                        'DAP' => 'DAP - Delivered At Place',
                                        'DPU' => 'DPU - Delivered at Place Unloaded',
                                        'DDP' => 'DDP - Delivered Duty Paid',
                                        'FAS' => 'FAS - Free Alongside Ship',
                                        'FOB' => 'FOB - Free On Board',
                                        'CFR' => 'CFR - Cost and Freight',
                                        'CIF' => 'CIF - Cost, Insurance and Freight',
                                    ])
                                    ->native(false)
                                    ->live()
                                    ->helperText('INCOTERMS 2020'),
                                
                                TextInput::make('incoterm_location')
                                    ->label('Location')
                                    ->maxLength(255)
                                    ->placeholder('e.g., Shanghai Port'),
                            ])
                            ->columns(2),
                        
                        Group::make()
                            ->schema([
                                Toggle::make('shipping_included_in_price')
                                    ->label('Shipping included in item prices')
                                    ->default(false)
                                    ->inline(false),
                                
                                Toggle::make('insurance_included_in_price')
                                    ->label('Insurance included in item prices')
                                    ->default(false)
                                    ->inline(false),
                            ])
                            ->columns(2),
                        
                        Textarea::make('delivery_address')
                            ->label('Delivery Address')
                            ->rows(3)
                            ->columnSpanFull(),
                        
                        Group::make()
                            ->schema([
                                DatePicker::make('expected_delivery_date')
                                    ->label('Expected Delivery')
                                    ->native(false),
                                
                                DatePicker::make('actual_delivery_date')
                                    ->label('Actual Delivery')
                                    ->native(false),
                            ])
                            ->columns(2),
                    ])
                    ->columns(1)
                    ->collapsible(),
                
                // ==========================================
                // SECTION 5: PAYMENT TERMS
                // ==========================================
                Section::make('Payment Terms')
                    ->description('Payment conditions and terms')
                    ->schema([
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
                    ])
                    ->columns(1)
                    ->collapsible(),
                
                // ==========================================
                // SECTION 6: ITEMS (REPEATER)
                // ==========================================
                Section::make('Purchase Order Items')
                    ->description('Add products and quantities')
                    ->schema([
                        Repeater::make('items')
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
                                                $set('unit_price', $product->unit_price ?? 0);
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
                                        $set('total', $get('quantity') * $get('unit_price'))
                                    )
                                    ->columnSpan(1),
                                
                                TextInput::make('unit_price')
                                    ->label('Unit Price')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => 
                                        $set('total', $get('quantity') * $get('unit_price'))
                                    )
                                    ->columnSpan(2),
                                
                                TextInput::make('total')
                                    ->label('Total')
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
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::updateTotals($get, $set);
                            })
                            ->deleteAction(
                                fn ($action) => $action->after(fn (Get $get, Set $set) => 
                                    self::updateTotals($get, $set)
                                )
                            )
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->collapsible(),
                
                // ==========================================
                // SECTION 7: ADDITIONAL COSTS
                // ==========================================
                Section::make('Additional Costs')
                    ->description('Shipping, insurance, and other costs')
                    ->schema([
                        Group::make()
                            ->schema([
                                TextInput::make('shipping_cost')
                                    ->label('Shipping Cost')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => 
                                        self::updateTotals($get, $set)
                                    )
                                    ->helperText('Highlighted for CIF/CFR'),
                                
                                TextInput::make('insurance_cost')
                                    ->label('Insurance Cost')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => 
                                        self::updateTotals($get, $set)
                                    ),
                                
                                TextInput::make('other_costs')
                                    ->label('Other Costs')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => 
                                        self::updateTotals($get, $set)
                                    ),
                            ])
                            ->columns(3),
                        
                        Group::make()
                            ->schema([
                                TextInput::make('discount')
                                    ->label('Discount')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => 
                                        self::updateTotals($get, $set)
                                    ),
                                
                                TextInput::make('tax')
                                    ->label('Tax')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => 
                                        self::updateTotals($get, $set)
                                    ),
                            ])
                            ->columns(2),
                    ])
                    ->columns(1)
                    ->collapsible(),
                
                // ==========================================
                // SECTION 8: TOTALS (READ-ONLY)
                // ==========================================
                Section::make('Totals')
                    ->description('Automatically calculated')
                    ->schema([
                        Group::make()
                            ->schema([
                                Placeholder::make('subtotal_display')
                                    ->label('Subtotal (Items)')
                                    ->content(fn (Get $get): string => 
                                        number_format($get('subtotal') ?? 0, 2)
                                    ),
                                
                                Placeholder::make('total_display')
                                    ->label('Total (PO Currency)')
                                    ->content(fn (Get $get): string => 
                                        number_format($get('total') ?? 0, 2)
                                    ),
                                
                                Placeholder::make('total_base_currency_display')
                                    ->label('Total (Base Currency)')
                                    ->content(fn (Get $get): string => 
                                        number_format($get('total_base_currency') ?? 0, 2)
                                    ),
                            ])
                            ->columns(3),
                        
                        // Hidden fields to store calculated values
                        TextInput::make('subtotal')
                            ->hidden()
                            ->dehydrated()
                            ->default(0),
                        
                        TextInput::make('total')
                            ->hidden()
                            ->dehydrated()
                            ->default(0),
                        
                        TextInput::make('total_base_currency')
                            ->hidden()
                            ->dehydrated()
                            ->default(0),
                    ])
                    ->columns(1)
                    ->collapsible(),
                
                // ==========================================
                // SECTION 9: NOTES & TERMS
                // ==========================================
                Section::make('Notes & Terms')
                    ->description('Additional information and conditions')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Internal Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                        
                        Textarea::make('terms_and_conditions')
                            ->label('Terms and Conditions')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->collapsible()
                    ->collapsed(),
                
                // ==========================================
                // SECTION 10: TRACKING (HIDDEN/AUTO)
                // ==========================================
                Section::make('Tracking')
                    ->description('System tracking information')
                    ->schema([
                        Group::make()
                            ->schema([
                                DateTimePicker::make('sent_at')
                                    ->label('Sent At')
                                    ->native(false)
                                    ->disabled(),
                                
                                DateTimePicker::make('confirmed_at')
                                    ->label('Confirmed At')
                                    ->native(false)
                                    ->disabled(),
                            ])
                            ->columns(2),
                        
                        Group::make()
                            ->schema([
                                TextInput::make('created_by')
                                    ->label('Created By')
                                    ->numeric()
                                    ->disabled(),
                                
                                TextInput::make('approved_by')
                                    ->label('Approved By')
                                    ->numeric()
                                    ->disabled(),
                            ])
                            ->columns(2),
                    ])
                    ->columns(1)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
    
    /**
     * Update all totals based on items and costs
     */
    protected static function updateTotals(Get $get, Set $set): void
    {
        // Calculate subtotal from items
        $items = collect($get('items') ?? []);
        $subtotal = $items->sum('total');
        
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
