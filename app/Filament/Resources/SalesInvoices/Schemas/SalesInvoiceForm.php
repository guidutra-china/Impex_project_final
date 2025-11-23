<?php

namespace App\Filament\Resources\SalesInvoices\Schemas;

use App\Models\Currency;
use App\Models\PaymentTerm;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SupplierQuote;
use App\Models\SalesInvoice;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Actions\Action as FormAction;
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

                    $quote = SupplierQuote::with(['order.client', 'currency', 'items.product'])->find($state);
                    if (!$quote) return;

                    // Fill client and currency from order
                    $set('client_id', $quote->order->client_id ?? null);
                    $set('currency_id', $quote->currency_id);
                    // Note: SupplierQuote doesn't have base_currency_id or exchange_rate_locked
                    // You may need to adjust this based on your actual schema

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

            Select::make('payment_term_id')
                ->label('Payment Terms')
                ->relationship('paymentTerm', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->reactive()
                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                    static::recalculateDueDate($get, $set);
                })
                ->helperText('Due date will be auto-calculated based on payment terms'),

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
                ->required()
                ->reactive()
                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                    static::recalculateDueDate($get, $set);
                }),

            DatePicker::make('shipment_date')
                ->label('Shipment Date')
                ->reactive()
                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                    static::recalculateDueDate($get, $set);
                })
                ->helperText('Required if payment term is based on shipment date'),

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

            Placeholder::make('supersedes_link')
                ->label('Previous Version')
                ->content(function (SalesInvoice $record): string {
                    if ($record->supersedes_id) {
                        $previous = $record->supersedes;
                        return "<a href='/admin/sales-invoices/{$previous->id}/edit' class='text-primary-600 hover:underline'>
                                    {$previous->invoice_number} (Rev {$previous->revision_number})
                                </a>";
                    }
                    return 'None';
                })
                ->html()
                ->visible(fn (SalesInvoice $record): bool => $record->supersedes_id !== null),

            Placeholder::make('superseded_by_link')
                ->label('Superseded By')
                ->content(function (SalesInvoice $record): string {
                    if ($record->superseded_by_id) {
                        $next = $record->supersededBy;
                        return "<a href='/admin/sales-invoices/{$next->id}/edit' class='text-primary-600 hover:underline font-bold'>
                                    {$next->invoice_number} (Rev {$next->revision_number})
                                </a>
                                <p class='text-xs text-gray-500 mt-1'>This invoice has been superseded. Please use the newer version.</p>";
                    }
                    return 'None';
                })
                ->html()
                ->visible(fn (SalesInvoice $record): bool => $record->superseded_by_id !== null),

            Placeholder::make('revision_reason')
                ->label('Revision Reason')
                ->content(fn (SalesInvoice $record): string => $record->revision_reason ?? 'N/A')
                ->visible(fn (SalesInvoice $record): bool => $record->revision_number > 1),
        ];
    }

    /**
     * Recalculate due date based on payment term stages
     */
    protected static function recalculateDueDate(Get $get, Set $set): void
    {
        $paymentTermId = $get('payment_term_id');
        if (!$paymentTermId) return;

        $paymentTerm = PaymentTerm::with('stages')->find($paymentTermId);
        if (!$paymentTerm || $paymentTerm->stages->isEmpty()) return;

        // Get the last stage (final payment)
        $lastStage = $paymentTerm->stages->sortByDesc('sort_order')->first();
        
        // Determine base date based on calculation_base
        $baseDate = null;
        if ($lastStage->calculation_base === 'shipment_date') {
            $shipmentDate = $get('shipment_date');
            if ($shipmentDate) {
                $baseDate = \Carbon\Carbon::parse($shipmentDate);
            }
        } else {
            // Default to invoice_date
            $invoiceDate = $get('invoice_date');
            if ($invoiceDate) {
                $baseDate = \Carbon\Carbon::parse($invoiceDate);
            }
        }

        // Calculate due date
        if ($baseDate) {
            $dueDate = $baseDate->addDays($lastStage->days);
            $set('due_date', $dueDate->format('Y-m-d'));
        }
    }
}
