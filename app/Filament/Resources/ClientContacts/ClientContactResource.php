<?php

namespace App\Filament\Resources\ClientContacts;

use App\Filament\Resources\ClientContacts\Pages\CreateClientContact;
use App\Filament\Resources\ClientContacts\Pages\EditClientContact;
use App\Filament\Resources\ClientContacts\Pages\ListClientContacts;
use App\Filament\Resources\ClientContacts\Schemas\ClientContactForm;
use App\Filament\Resources\ClientContacts\Tables\ClientContactsTable;
use App\Models\ClientContact;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ClientContactResource extends Resource
{
    protected static ?string $model = ClientContact::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 4;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    public static function form(Schema $schema): Schema
    {
        return ClientContactForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClientContactsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClientContacts::route('/'),
            'create' => CreateClientContact::route('/create'),
            'edit' => EditClientContact::route('/{record}/edit'),
        ];
    }
}
