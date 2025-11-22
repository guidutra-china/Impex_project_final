<?php

namespace App\Filament\Resources\PurchaseOrders\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PurchaseOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('po_number')
                    ->required(),
                TextInput::make('revision_number')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('order_id')
                    ->numeric(),
                Select::make('supplier_quote_id')
                    ->relationship('supplierQuote', 'id'),
                Select::make('supplier_id')
                    ->relationship('supplier', 'name')
                    ->required(),
                Select::make('currency_id')
                    ->relationship('currency', 'name')
                    ->required(),
                TextInput::make('exchange_rate')
                    ->required()
                    ->numeric(),
                Select::make('base_currency_id')
                    ->relationship('baseCurrency', 'name')
                    ->required(),
                TextInput::make('subtotal')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('shipping_cost')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('insurance_cost')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('other_costs')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('discount')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('tax')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('total')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('total_base_currency')
                    ->required()
                    ->numeric()
                    ->default(0),
                Select::make('incoterm')
                    ->options([
            'EXW' => 'E x w',
            'FCA' => 'F c a',
            'CPT' => 'C p t',
            'CIP' => 'C i p',
            'DAP' => 'D a p',
            'DPU' => 'D p u',
            'DDP' => 'D d p',
            'FAS' => 'F a s',
            'FOB' => 'F o b',
            'CFR' => 'C f r',
            'CIF' => 'C i f',
        ]),
                TextInput::make('incoterm_location'),
                Toggle::make('shipping_included_in_price')
                    ->required(),
                Toggle::make('insurance_included_in_price')
                    ->required(),
                Select::make('payment_term_id')
                    ->relationship('paymentTerm', 'name'),
                Textarea::make('payment_terms_text')
                    ->columnSpanFull(),
                Textarea::make('delivery_address')
                    ->columnSpanFull(),
                DatePicker::make('expected_delivery_date'),
                DatePicker::make('actual_delivery_date'),
                Select::make('status')
                    ->options([
            'draft' => 'Draft',
            'pending_approval' => 'Pending approval',
            'approved' => 'Approved',
            'sent' => 'Sent',
            'confirmed' => 'Confirmed',
            'partially_received' => 'Partially received',
            'received' => 'Received',
            'cancelled' => 'Cancelled',
            'closed' => 'Closed',
        ])
                    ->default('draft')
                    ->required(),
                DatePicker::make('po_date')
                    ->required(),
                DateTimePicker::make('sent_at'),
                DateTimePicker::make('confirmed_at'),
                Textarea::make('notes')
                    ->columnSpanFull(),
                Textarea::make('terms_and_conditions')
                    ->columnSpanFull(),
                TextInput::make('created_by')
                    ->numeric(),
                TextInput::make('approved_by')
                    ->numeric(),
            ]);
    }
}
