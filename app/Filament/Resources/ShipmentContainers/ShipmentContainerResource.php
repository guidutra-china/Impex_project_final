<?php

namespace App\Filament\Resources\ShipmentContainers;

use App\Filament\Resources\ShipmentContainers\Pages\CreateShipmentContainer;
use App\Filament\Resources\ShipmentContainers\Pages\EditShipmentContainer;
use App\Filament\Resources\ShipmentContainers\Pages\ListShipmentContainers;
use App\Filament\Resources\ShipmentContainers\Schemas\ShipmentContainerForm;
use App\Filament\Resources\ShipmentContainers\Tables\ShipmentContainersTable;
use App\Models\ShipmentContainer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ShipmentContainerResource extends Resource
{
    protected static ?string $model = ShipmentContainer::class;

    protected static UnitEnum|string|null $navigationGroup = 'Logistics & Shipping';

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedCubeTransparent;

    protected static ?int $navigationSort = 11;

    protected static ?string $label = 'Shipment Container';

    protected static ?string $pluralLabel = 'Shipment Containers';

    public static function form(Schema $schema): Schema
    {
        return ShipmentContainerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ShipmentContainersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\Shipments\RelationManagers\ContainerItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShipmentContainers::route('/'),
            'create' => CreateShipmentContainer::route('/create'),
            'edit' => EditShipmentContainer::route('/{record}/edit'),
        ];
    }
}
