<?php

namespace App\Filament\Resources\SupplierPerformanceMetrics;

use App\Filament\Resources\SupplierPerformanceMetrics\Pages\CreateSupplierPerformanceMetric;
use App\Filament\Resources\SupplierPerformanceMetrics\Pages\EditSupplierPerformanceMetric;
use App\Filament\Resources\SupplierPerformanceMetrics\Pages\ListSupplierPerformanceMetrics;
use App\Filament\Resources\SupplierPerformanceMetrics\Schemas\SupplierPerformanceMetricForm;
use App\Filament\Resources\SupplierPerformanceMetrics\Tables\SupplierPerformanceMetricsTable;
use App\Models\SupplierPerformanceMetric;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SupplierPerformanceMetricResource extends Resource
{
    protected static ?string $model = SupplierPerformanceMetric::class;

    protected static UnitEnum|string|null $navigationGroup = 'Suppliers';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return SupplierPerformanceMetricForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupplierPerformanceMetricsTable::configure($table);
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
            'index' => ListSupplierPerformanceMetrics::route('/'),
            'create' => CreateSupplierPerformanceMetric::route('/create'),
            'edit' => EditSupplierPerformanceMetric::route('/{record}/edit'),
        ];
    }
}
