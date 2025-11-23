<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanySettingsResource\Pages;
use App\Models\CompanySetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class CompanySettingsResource extends Resource
{
    protected static ?string $model = CompanySetting::class;

    protected static ?string $navigationLabel = 'Company Settings';

    protected static ?string $modelLabel = 'Company Settings';

    protected static ?int $navigationSort = 100;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCompanySettings::route('/'),
        ];
    }
}
