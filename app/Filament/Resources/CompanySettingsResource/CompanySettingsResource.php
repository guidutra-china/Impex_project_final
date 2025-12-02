<?php

namespace App\Filament\Resources\CompanySettingsResource;

use App\Models\CompanySetting;
use BackedEnum;
use Filament\Resources\Resource;
use UnitEnum;

class CompanySettingsResource extends Resource
{
    protected static ?string $model = CompanySetting::class;

    protected static UnitEnum|string|null $navigationGroup = 'Configuration';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Company Settings';

    protected static ?string $modelLabel = 'Company Settings';

    

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCompanySettings::route('/'),
        ];
    }
}
