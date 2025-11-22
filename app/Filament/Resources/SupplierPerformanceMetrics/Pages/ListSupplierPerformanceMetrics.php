<?php

namespace App\Filament\Resources\SupplierPerformanceMetrics\Pages;

use App\Filament\Resources\SupplierPerformanceMetrics\SupplierPerformanceMetricResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSupplierPerformanceMetrics extends ListRecords
{
    protected static string $resource = SupplierPerformanceMetricResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
