<?php

namespace App\Filament\Resources\PackingUnits;

use App\Filament\Resources\PackingUnits\Pages\ManagePackingUnits;
use App\Models\ContainerType;
use BackedEnum;
use Filament\Resources\Resource;
use UnitEnum;

class PackingUnitResource extends Resource
{
    // Use ContainerType as base model (we'll handle both types in the page)
    protected static ?string $model = ContainerType::class;

    protected static UnitEnum|string|null $navigationGroup = 'Logistics & Shipping';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-cube-transparent';

    protected static ?int $navigationSort = 50;

    protected static ?string $navigationLabel = 'Packing Units';

    protected static ?string $modelLabel = 'Packing Unit';

    protected static ?string $pluralModelLabel = 'Packing Units';

    public static function getPages(): array
    {
        return [
            'index' => ManagePackingUnits::route('/'),
        ];
    }
}
