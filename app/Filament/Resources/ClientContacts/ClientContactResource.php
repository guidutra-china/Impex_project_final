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

class ClientContactResource extends Resource
{
    protected static ?string $model = ClientContact::class;

    protected static UnitEnum|string|null $navigationGroup = 'Sales & Orders';

    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static ?int $navigationSort = 5;

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
