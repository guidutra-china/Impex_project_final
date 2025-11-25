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

class SupplierPerformanceMetricResource extends Resource
{
    protected static ?string $model = SupplierPerformanceMetric::class;

    protected static ?string $navigationGroup = 'Suppliers';

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?int $navigationSort = 3;


    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

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
