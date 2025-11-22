<?php

namespace App\Filament\Resources\SupplierPayments\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class SupplierPaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('payment_number')
                    ->required(),
                Select::make('supplier_id')
                    ->relationship('supplier', 'name')
                    ->required(),
                Select::make('bank_account_id')
                    ->relationship('bankAccount', 'id')
                    ->required(),
                Select::make('payment_method_id')
                    ->relationship('paymentMethod', 'name')
                    ->required(),
                Select::make('currency_id')
                    ->relationship('currency', 'name')
                    ->required(),
                TextInput::make('amount')
                    ->required()
                    ->numeric(),
                TextInput::make('fee')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('net_amount')
                    ->required()
                    ->numeric(),
                TextInput::make('exchange_rate')
                    ->numeric(),
                TextInput::make('amount_base_currency')
                    ->numeric(),
                DatePicker::make('payment_date')
                    ->required(),
                TextInput::make('reference_number'),
                TextInput::make('transaction_id'),
                Select::make('status')
                    ->options([
            'pending' => 'Pending',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
        ])
                    ->default('pending')
                    ->required(),
                Textarea::make('notes')
                    ->columnSpanFull(),
                TextInput::make('created_by')
                    ->numeric(),
            ]);
    }
}
