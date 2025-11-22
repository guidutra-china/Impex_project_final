<?php

namespace App\Filament\Resources\SalesInvoices\Schemas;

use App\Models\Currency;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Quote;
use App\Models\SalesInvoice;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class SalesInvoiceForm
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

                        Section::make('Invoice Items')
                            ->schema([
                                static::getItemsRepeater(),
                            ]),

                        Section::make('Additional Costs')
                            ->schema(static::getCostsComponents())
                            ->columns(2)
                            ->collapsible(),

                        Section::make('Totals')
                            ->schema(static::getTotalsComponents())
                            ->columns(4)
                            ->collapsible(),

                        Section::make('Payment Information')
                            ->schema(static::getPaymentComponents())
                            ->columns(2)
                            ->collapsible(),

                        Section::make('Notes & Terms')
                            ->schema(static::getNotesComponents())
                            ->collapsed()
                            ->collapsible(),
                    ])
                    ->columnSpan(['lg' => fn (?SalesInvoice $record) => $record === null ? 3 : 2]),

                Group::make()
                    ->schema([
                        Section::make()
                            ->schema(static::getSidebarComponents())
                            ->hidden(fn (?SalesInvoice $record) => $record === null),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    protected static function getDetailsComponents(): array
    {
        return [
            TextInput::make('invoice_number')
                ->label('Invoice Number')
                ->default(fn () => SalesInvoice::generateInvoiceNumber())
                ->disabled()
                ->dehydrated()
                ->required(),

            TextInput::make('revision_number')
                ->label('Revision')
                ->default(1)
                ->numeric()
                ->disabled()
                ->dehydrated(),

            Select::make('status')
                ->options([
                    'draft' => 'Draft',
                    'sent' => 'Sent',
                    'paid' => 'Paid',
                    'overdue' => 'Overdue',
                    'cancelled' => 'Cancelled',
                    'superseded' => 'Superseded',
                ])
                ->default('draft')
                ->required(),

            Select::make('quote_id')
                ->label('Quote')
                ->relationship('quote', 'quote_number')
                ->searchable()
                ->preload()
                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                    if (!$state) return;

                    $quote = Quote::with(['client', 'currency', 'baseCurrency', 'items.product'])->find($state);
                    if (!$quote) return;

                    // Fill client and currency
                    $set('client_id', $quote->client_id);
                    $set('currency_id', $quote->currency_id);
                    $set('base_currency_id', $quote->base_currency_id);
                    $set('exchange_rate', $quote->exchange_rate_locked);

                    // Fill items from Quote
                    $items = $quote->items->map(function ($item) {
                        return [
                            'product_id' => $item->product_id,
                            'product_name' => $item->product_name,
                            'product_sku' => $item->product_sku,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price / 100, // Convert from cents
                            'commission' => $item->commission / 100,
                            'total' => $item->total_price / 100,
                            'quote_item_id' => $item->id,
                            'notes' => $item->notes,
                        ];
                    })->toArray();

                    $set('items', $items);
                }),

            Select::make('client_id')
                ->label('Client')
                ->relationship('client', 'name')
                ->searchable()
                ->preload()
                ->required(),

            Select::make('purchase_order_ids')
                ->label('Purchase Orders')
                ->relationship('purchaseOrders', 'po_number')
                ->multiple()
                ->searchable()
                ->preload()
                ->helperText('Select multiple POs to consolidate into one invoice'),

            DatePicker::make('invoice_date')
                ->label('Invoice Date')
                ->default(now())
                ->required(),

            DatePicker::make('due_date')
                ->label('Due Date')
                ->default(now()->addDays(30))
                ->required(),

            Select::make('currency_id')
                ->label('Invoice Currency')
                ->relationship('currency', 'code')
                ->searchable()
                ->preload()
                ->required()
                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                    $baseCurrencyId = $get('base_currency_id');
                    
                    if ($state == $baseCurrencyId) {
                        $set('exchange_rate', 1);
                    } else {
                        $currency = Currency::find($state);
                        if ($currency) {
                            $set('exchange_rate', $currency->exchange_rate);
                        }
                    }
                }),

            TextInput::make('exchange_rate')
                ->label('Exchange Rate')
                ->numeric()
                ->default(1)
                ->step('any')
                ->minValue(0.000001)
                ->required()
                ->helperText('Rate locked at invoice creation'),

            Select::make('base_currency_id')
                ->label('Base Currency')
                ->relationship('baseCurrency', 'code')
                ->default(fn () => Currency::where('is_base', true)->first()?->id)
                ->required()
                ->disabled()
                ->dehydrated(),
        ];
    }

    protected static function getItemsRepeater(): Repeater
    {
        return Repeater::make('items')
            ->relationship()
            ->schema([
                Select::make('product_id')
                    ->label('Product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpan(2)
                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                        if (!$state) return;

                        $product = Product::find($state);
                        if ($product) {
                            $set('product_name', $product->name);
                            $set('product_sku', $product->sku);
                            $set('unit_price', $product->price / 100); // Convert from cents
                        }
                    }),

                Select::make('purchase_order_id')
                    ->label('Source PO')
                    ->relationship('purchaseOrder', 'po_number')
                    ->searchable()
                    ->preload()
                    ->helperText('Track which PO this item came from'),

                TextInput::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->default(1)
                    ->minValue(0.01)
                    ->required()
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        $quantity = (float) $get('quantity');
                        $unitPrice = (float) $get('unit_price');
                        $commission = (float) $get('commission');
                        $total = ($quantity * $unitPrice) + $commission;
                        $set('total', $total);
                    }),

                TextInput::make('unit_price')
                    ->label('Unit Price')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->required()
                    ->prefix('$')
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        $quantity = (float) $get('quantity');
                        $unitPrice = (float) $get('unit_price');
                        $commission = (float) $get('commission');
                        $total = ($quantity * $unitPrice) + $commission;
                        $set('total', $total);
                    }),

                TextInput::make('commission')
                    ->label('Commission')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->prefix('$')
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        $quantity = (float) $get('quantity');
                        $unitPrice = (float) $get('unit_price');
                        $commission = (float) $get('commission');
                        $total = ($quantity * $unitPrice) + $commission;
                        $set('total', $total);
                    }),

                TextInput::make('total')
                    ->label('Total')
                    ->numeric()
                    ->disabled()
                    ->dehydrated()
                    ->prefix('$'),

                Textarea::make('notes')
                    ->label('Notes')
                    ->rows(2)
                    ->columnSpanFull(),

                // Hidden fields for caching
                TextInput::make('product_name')->hidden()->dehydrated(),
                TextInput::make('product_sku')->hidden()->dehydrated(),
                TextInput::make('purchase_order_item_id')->hidden()->dehydrated(),
                TextInput::make('quote_item_id')->hidden()->dehydrated(),
            ])
            ->columns(6)
            ->defaultItems(1)
            ->reorderable()
            ->collapsible()
            ->itemLabel(fn (array $state): ?string => $state['product_name'] ?? null);
    }

    protected static function getCostsComponents(): array
    {
        return [
            TextInput::make('tax')
                ->label('Tax')
                ->numeric()
                ->default(0)
                ->minValue(0)
                ->prefix('$'),
        ];
    }

    protected static function getTotalsComponents(): array
    {
        return [
            TextInput::make('subtotal')
                ->label('Subtotal')
                ->numeric()
                ->disabled()
                ->prefix('$')
                ->helperText('Auto-calculated from items'),

            TextInput::make('commission')
                ->label('Total Commission')
                ->numeric()
                ->disabled()
                ->prefix('$')
                ->helperText('Sum of item commissions'),

            TextInput::make('total')
                ->label('Total')
                ->numeric()
                ->disabled()
                ->prefix('$')
                ->helperText('Subtotal + Tax'),

            TextInput::make('total_base_currency')
                ->label('Total (Base Currency)')
                ->numeric()
                ->disabled()
                ->prefix('$')
                ->helperText('Total × Exchange Rate'),
        ];
    }

    protected static function getPaymentComponents(): array
    {
        return [
            DatePicker::make('payment_date')
                ->label('Payment Date'),

            Select::make('payment_method')
                ->label('Payment Method')
                ->options([
                    'bank_transfer' => 'Bank Transfer',
                    'credit_card' => 'Credit Card',
                    'cash' => 'Cash',
                    'check' => 'Check',
                    'paypal' => 'PayPal',
                    'other' => 'Other',
                ]),

            Textarea::make('payment_reference')
                ->label('Payment Reference')
                ->rows(2)
                ->columnSpanFull()
                ->helperText('Transaction ID, check number, etc.'),
        ];
    }

    protected static function getNotesComponents(): array
    {
        return [
            Textarea::make('notes')
                ->label('Internal Notes')
                ->rows(3)
                ->columnSpanFull(),

            Textarea::make('terms_and_conditions')
                ->label('Terms and Conditions')
                ->rows(3)
                ->columnSpanFull(),
        ];
    }

    protected static function getSidebarComponents(): array
    {
        return [
            Placeholder::make('created_at')
                ->label('Created')
                ->content(fn (SalesInvoice $record): string => $record->created_at->diffForHumans()),

            Placeholder::make('updated_at')
                ->label('Last modified')
                ->content(fn (SalesInvoice $record): string => $record->updated_at->diffForHumans()),

            Placeholder::make('sent_at')
                ->label('Sent to client')
                ->content(fn (SalesInvoice $record): string => $record->sent_at ? $record->sent_at->diffForHumans() : 'Not sent'),

            Placeholder::make('paid_at')
                ->label('Paid')
                ->content(fn (SalesInvoice $record): string => $record->paid_at ? $record->paid_at->diffForHumans() : 'Not paid'),
        ];
    }
}
