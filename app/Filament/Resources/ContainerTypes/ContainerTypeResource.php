<?php

namespace App\Filament\Resources\ContainerTypes;

use App\Filament\Resources\ContainerTypes\Pages\CreateContainerType;
use App\Filament\Resources\ContainerTypes\Pages\EditContainerType;
use App\Filament\Resources\ContainerTypes\Pages\ListContainerTypes;
use App\Filament\Resources\ContainerTypes\Schemas\ContainerTypeForm;
use App\Filament\Resources\ContainerTypes\Tables\ContainerTypesTable;
use App\Models\ContainerType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class ContainerTypeResource extends Resource
{
    protected static ?string $model = ContainerType::class;

    protected static UnitEnum|string|null $navigationGroup = 'Shipments';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-cube';

    protected static ?int $navigationSort = 90;

    protected static ?string $navigationLabel = 'Container Types';

    protected static ?string $modelLabel = 'Container Type';

    protected static ?string $pluralModelLabel = 'Container Types';

    public static function form(Schema $schema): Schema
    {
        return ContainerTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContainerTypesTable::configure($table);
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
            'index' => ListContainerTypes::route('/'),
            'create' => CreateContainerType::route('/create'),
            'edit' => EditContainerType::route('/{record}/edit'),
        ];
    }
}
