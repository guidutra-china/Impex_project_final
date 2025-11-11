<?php

namespace App\Filament\Resources\SupplierContacts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SupplierContactForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Contact Name')
                    ->required()
                    ->maxLength(255),
                Select::make('supplier_id')
                    ->label('Supplier')
                    ->searchable()
                    ->preload()
                    ->relationship('supplier' , 'name' )
                    ->required(),
                TextInput::make('email')
                    ->label('Email Address')
                    ->email()
                    ->required()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->label('Phone Number')
                    ->tel()
                    ->maxLength(20),
                TextInput::make('wechat')
                    ->label('WeChat ID')
                    ->maxLength(50),
                TextInput::make('function')
                ->label('Function')
                ->maxLength(50),

            ]);
    }
}
