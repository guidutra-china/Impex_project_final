<?php

namespace App\Filament\Resources\CompanySettingsResource;

use App\Models\CompanySetting;
use BackedEnum;
use Filament\Resources\Resource;
use UnitEnum;

class CompanySettingsResource extends Resource
{
    protected static ?string $model = CompanySetting::class;

    protected static UnitEnum|string|null $navigationGroup = 'Settings';

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.settings');
    }

    public static function getModelLabel(): string
    {
        return __('navigation.company_settings');
    }

    public static function getPluralModelLabel(): string
    {
        return __('navigation.company_settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.company_settings');
    }

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Company Settings';

    protected static ?string $modelLabel = 'Company Settings';

    

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCompanySettings::route('/'),
        ];
    }
}
