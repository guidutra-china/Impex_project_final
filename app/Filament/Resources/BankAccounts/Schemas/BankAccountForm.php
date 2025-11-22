<?php

namespace App\Filament\Resources\BankAccounts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BankAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('account_name')
                    ->required(),
                TextInput::make('account_number')
                    ->required(),
                TextInput::make('account_type')
                    ->required(),
                TextInput::make('bank_name')
                    ->required(),
                TextInput::make('bank_branch'),
                TextInput::make('swift_code'),
                TextInput::make('iban'),
                TextInput::make('routing_number'),
                Select::make('currency_id')
                    ->relationship('currency', 'name')
                    ->required(),
                TextInput::make('current_balance')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('available_balance')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('daily_limit')
                    ->numeric(),
                TextInput::make('monthly_limit')
                    ->numeric(),
                Toggle::make('is_active')
                    ->required(),
                Toggle::make('is_default')
                    ->required(),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
