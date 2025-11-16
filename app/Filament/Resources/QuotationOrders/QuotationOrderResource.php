<?php

namespace App\Filament\Resources\SupplierQuotes\QuotationOrders;

use App\Filament\Resources\SupplierQuotes\QuotationOrders\Pages\CreateQuotationOrder;
use App\Filament\Resources\SupplierQuotes\QuotationOrders\Pages\EditQuotationOrder;
use App\Filament\Resources\SupplierQuotes\QuotationOrders\Pages\ListQuotationOrders;
use App\Filament\Resources\SupplierQuotes\QuotationOrders\Schemas\QuotationOrderForm;
use App\Filament\Resources\SupplierQuotes\QuotationOrders\Tables\QuotationOrdersTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class QuotationOrderResource extends Resource
{
    protected static ?string $model = QuotationOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return QuotationOrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuotationOrdersTable::configure($table);
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
            'index' => ListQuotationOrders::route('/'),
            'create' => CreateQuotationOrder::route('/create'),
            'edit' => EditQuotationOrder::route('/{record}/edit'),
        ];
    }
}
