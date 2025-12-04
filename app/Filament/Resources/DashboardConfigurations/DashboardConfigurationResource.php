<?php

namespace App\Filament\Resources\DashboardConfigurations;

use App\Filament\Resources\DashboardConfigurations\Pages\EditDashboardConfiguration;
use App\Filament\Resources\DashboardConfigurations\Pages\ListDashboardConfigurations;
use App\Filament\Resources\DashboardConfigurations\Schemas\DashboardConfigurationForm;
use App\Filament\Resources\DashboardConfigurations\Tables\DashboardConfigurationsTable;
use App\Models\DashboardConfiguration;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class DashboardConfigurationResource extends Resource
{
    protected static ?string $model = DashboardConfiguration::class;

    protected static UnitEnum|string|null $navigationGroup = 'Settings';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 100;

    protected static ?string $label = 'Dashboard Configuration';

    protected static ?string $pluralLabel = 'Dashboard Configurations';

    public static function form(Schema $schema): Schema
    {
        return DashboardConfigurationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DashboardConfigurationsTable::configure($table);
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
            'index' => ListDashboardConfigurations::route('/'),
            'edit' => EditDashboardConfiguration::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
