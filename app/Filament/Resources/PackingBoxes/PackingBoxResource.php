<?php

namespace App\Filament\Resources\PackingBoxes;

use App\Models\PackingBox;
use Filament\Resources\Resource;

class PackingBoxResource extends Resource
{
    protected static ?string $model = PackingBox::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Logistics & Shipping';

    protected static ?string $navigationLabel = 'Packing Boxes';

    protected static ?int $navigationSort = 3;

    protected static bool $shouldRegisterNavigation = false; // Hidden from main nav

    public static function getRelations(): array
    {
        return [
            RelationManagers\PackingBoxItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'edit' => Pages\EditPackingBox::route('/{record}/edit'),
        ];
    }
}
