<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('order_number')
                    ->required(),
                Select::make('payment_term_id')
                    ->relationship('paymentTerm', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->default(fn () => \App\Models\PaymentTerm::where('is_default', true)->first()?->id),
                TextInput::make('client_id')
                    ->required()
                    ->numeric(),
                DatePicker::make('order_date')
                    ->required(),
                // Removed old payment_terms_days field
                TextInput::make('total_amount_cents')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('currency_id')
                    ->required()
                    ->numeric(),
                TextInput::make('exchange_rate_to_usd')
                    ->numeric(),
                TextInput::make('total_amount_usd_cents')
                    ->numeric(),
                TextInput::make('invoice_number'),
                TextInput::make('status')
                    ->required()
                    ->default('New'),
                TextInput::make('payment_status')
                    ->required()
                    ->default('unpaid'),
                TextInput::make('shipping_company'),
                TextInput::make('shipping_document'),
                TextInput::make('shipping_value_cents')
                    ->numeric(),
                TextInput::make('shipping_value_usd_cents')
                    ->numeric(),
                DatePicker::make('etd'),
                DatePicker::make('eta'),
            ]);
    }
}
