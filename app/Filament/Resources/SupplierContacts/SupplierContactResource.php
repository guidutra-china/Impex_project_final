<?php

namespace App\Filament\Resources\SupplierContacts;

use App\Filament\Resources\SupplierContacts\Pages\CreateSupplierContact;
use App\Filament\Resources\SupplierContacts\Pages\EditSupplierContact;
use App\Filament\Resources\SupplierContacts\Pages\ListSupplierContacts;
use App\Filament\Resources\SupplierContacts\Schemas\SupplierContactForm;
use App\Filament\Resources\SupplierContacts\Tables\SupplierContactsTable;
use App\Models\SupplierContact;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SupplierContactResource extends Resource
{
    protected static ?string $model = SupplierContact::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 3;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    public static function form(Schema $schema): Schema
    {
        return SupplierContactForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupplierContactsTable::configure($table);
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
            'index' => ListSupplierContacts::route('/'),
            'create' => CreateSupplierContact::route('/create'),
            'edit' => EditSupplierContact::route('/{record}/edit'),
        ];
    }
}
