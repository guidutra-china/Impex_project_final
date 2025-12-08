<?php

namespace App\Filament\Resources\ClientContacts\Schemas;

use App\Enums\ContactFunctionEnum;
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
                    ->label(__('fields.contact_person'))
                    ->required()
                    ->maxLength(255),
                Select::make('client_id')
                    ->label(__('fields.customer'))
                    ->searchable()
                    ->preload()
                    ->relationship('client', 'name')
                    ->required()
                    ->hidden(fn ($livewire) => $livewire instanceof RelationManager),

                TextInput::make('email')
                    ->label(__('fields.email'))
                    ->email()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->label(__('fields.phone'))
                    ->tel()
                    ->maxLength(20),
                TextInput::make('wechat')
                    ->label('WeChat ID')
                    ->maxLength(50),
                Select::make('function')
                    ->label('Function')
                    ->options(ContactFunctionEnum::class)
                    ->searchable()
                    ->nullable(),
            ]);
    }
}

