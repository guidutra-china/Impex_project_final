<?php

namespace App\Filament\Resources\PaymentTerms\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PaymentTermForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Textarea::make('description')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Toggle::make('is_default')
                    ->label('Set as Default')
                    ->helperText('Only one payment term can be set as default.')
                    ->columnSpanFull(),
            ]);
    }
}
