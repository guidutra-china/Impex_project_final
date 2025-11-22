<?php

namespace App\Filament\Resources\SupplierPerformanceMetrics\Pages;

use App\Filament\Resources\SupplierPerformanceMetrics\SupplierPerformanceMetricResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSupplierPerformanceMetric extends EditRecord
{
    protected static string $resource = SupplierPerformanceMetricResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
