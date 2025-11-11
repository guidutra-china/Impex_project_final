<?php

namespace App\Filament\Resources\Currencies\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CurrencyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('name_plural')
                    ->required(),
                TextInput::make('symbol')
                    ->required(),
                Toggle::make('is_base')
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
