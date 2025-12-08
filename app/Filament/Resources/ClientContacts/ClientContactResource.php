<?php

namespace App\Filament\Resources\ClientContacts;

use App\Filament\Resources\ClientContacts\Pages\CreateClientContact;
use App\Filament\Resources\ClientContacts\Pages\EditClientContact;
use App\Filament\Resources\ClientContacts\Pages\ListClientContacts;
use App\Filament\Resources\ClientContacts\Schemas\ClientContactForm;
use App\Filament\Resources\SupplierQuotes\ClientContacts\Tables\ClientContactsTable;
use App\Models\ClientContact;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ClientContactResource extends Resource
{
    protected static ?string $model = ClientContact::class;

    protected static UnitEnum|string|null $navigationGroup = 'Contacts';

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.contacts');
    }

    public static function getModelLabel(): string
    {
        return __('navigation.client_contacts');
    }

    public static function getPluralModelLabel(): string
    {
        return __('navigation.client_contacts');
    }

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-user';

    protected static ?int $navigationSort = 20;

    protected static bool $shouldRegisterNavigation = false;

    

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
