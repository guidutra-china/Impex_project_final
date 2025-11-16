<?php

namespace App\Filament\Resources\SupplierQuotes\Products;

use App\Filament\Resources\SupplierQuotes\Products\Pages\CreateProduct;
use App\Filament\Resources\SupplierQuotes\Products\Pages\EditProduct;
use App\Filament\Resources\SupplierQuotes\Products\Pages\ListProducts;
use App\Filament\Resources\SupplierQuotes\Products\RelationManagers\BomItemsRelationManager;
use App\Filament\Resources\SupplierQuotes\Products\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\SupplierQuotes\Products\RelationManagers\FeaturesRelationManager;
use App\Filament\Resources\SupplierQuotes\Products\RelationManagers\PhotosRelationManager;
use App\Filament\Resources\SupplierQuotes\Products\Schemas\ProductForm;
use App\Filament\Resources\SupplierQuotes\Products\Tables\ProductsTable;
use App\Models\Product;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;
    protected static string|UnitEnum|null $navigationGroup = 'Products';


    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            BomItemsRelationManager::class,
            FeaturesRelationManager::class,
            PhotosRelationManager::class,
            DocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
