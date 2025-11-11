<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('order_number')
                    ->required(),
                TextInput::make('client_id')
                    ->required()
                    ->numeric(),
                DatePicker::make('order_date')
                    ->required(),
                TextInput::make('payment_terms_days')
                    ->required()
                    ->numeric()
                    ->default(30),
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
