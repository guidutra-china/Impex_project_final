<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanySettings\Schemas\CompanySettingsForm;
use App\Filament\Resources\CompanySettingsResource\Pages;
use App\Models\CompanySetting;
use Filament\Resources\Resource;

class CompanySettingsResource extends Resource
{
    protected static ?string $model = CompanySetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Company Settings';

    protected static ?string $modelLabel = 'Company Settings';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCompanySettings::route('/'),
        ];
    }
}
