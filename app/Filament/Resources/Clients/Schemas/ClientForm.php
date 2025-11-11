<?php

namespace App\Filament\Resources\Clients\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('address'),
                TextInput::make('city'),
                TextInput::make('state'),
                TextInput::make('zip'),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('country'),
                TextInput::make('website'),
                TextInput::make('tax_number')
                    ->placeholder('00.000.000/0000-00'),
            ]);
    }
}
