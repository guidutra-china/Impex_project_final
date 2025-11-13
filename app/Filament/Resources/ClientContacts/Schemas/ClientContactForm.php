<?php

namespace App\Filament\Resources\ClientContacts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;

class ClientContactForm
{
    public static function configure(Form|Schema $schema): Form|Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Contact Name')
                    ->required()
                    ->maxLength(255),
                Select::make('client_id')
                    ->label('Client')
                    ->searchable()
                    ->preload()
                    ->relationship('client', 'name')
                    ->required()
                    ->hidden(fn ($livewire) => $livewire instanceof RelationManager),

                TextInput::make('email')
                    ->label('Email Address')
                    ->email()
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

