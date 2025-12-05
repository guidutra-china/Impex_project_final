<?php

namespace App\Filament\Resources\PackingBoxTypes;

use App\Filament\Resources\PackingBoxTypes\Pages\CreatePackingBoxType;
use App\Filament\Resources\PackingBoxTypes\Pages\EditPackingBoxType;
use App\Filament\Resources\PackingBoxTypes\Pages\ListPackingBoxTypes;
use App\Filament\Resources\PackingBoxTypes\Schemas\PackingBoxTypeForm;
use App\Filament\Resources\PackingBoxTypes\Tables\PackingBoxTypesTable;
use App\Models\PackingBoxType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class PackingBoxTypeResource extends Resource
{
    protected static ?string $model = PackingBoxType::class;

    protected static UnitEnum|string|null $navigationGroup = 'Shipments';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-archive-box';

    protected static ?int $navigationSort = 91;

    protected static ?string $navigationLabel = 'Box Types';

    protected static ?string $modelLabel = 'Packing Box Type';

    protected static ?string $pluralModelLabel = 'Packing Box Types';

    public static function form(Schema $schema): Schema
    {
        return PackingBoxTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PackingBoxTypesTable::configure($table);
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
            'index' => ListPackingBoxTypes::route('/'),
            'create' => CreatePackingBoxType::route('/create'),
            'edit' => EditPackingBoxType::route('/{record}/edit'),
        ];
    }
}
