<?php

namespace App\Filament\Resources\SupplierQuotes;

use App\Filament\Resources\SupplierQuotes\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\SupplierQuotes\Schemas\SupplierQuoteForm;
use App\Filament\Resources\SupplierQuotes\Pages\CreateSupplierQuote;
use App\Filament\Resources\SupplierQuotes\Pages\EditSupplierQuote;
use App\Filament\Resources\SupplierQuotes\Pages\ListSupplierQuotes;
use App\Filament\Resources\SupplierQuotes\Tables\SupplierQuotesTable;
use App\Models\SupplierQuote;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SupplierQuoteResource extends Resource
{
    protected static ?string $model = SupplierQuote::class;

    protected static UnitEnum|string|null $navigationGroup = 'Sales & Quotations';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 20;

    

    

    protected static ?string $navigationLabel = 'Supplier Quotes';

    public static function form(Schema $schema): Schema
    {
        return SupplierQuoteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupplierQuotesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupplierQuotes::route('/'),
            'create' => CreateSupplierQuote::route('/create'),
            'edit' => EditSupplierQuote::route('/{record}/edit'),
        ];
    }
}

