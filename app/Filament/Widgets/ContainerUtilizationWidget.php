<?php

namespace App\Filament\Widgets;

use App\Models\Shipment;
use Filament\Widgets\Widget;

class ContainerUtilizationWidget extends Widget
{
    protected string $view = 'filament.widgets.container-utilization-widget';

    protected static ?int $sort = 2;

    public ?Shipment $shipment = null;

    public function mount(): void
    {
        if (request()->has('shipmentId')) {
            $this->shipment = Shipment::find(request('shipmentId'));
        }
    }

    public function getContainerUtilization(): array
    {
        if (!$this->shipment) {
            return [];
        }

        $containers = $this->shipment->containers()->get();

        return $containers->map(function ($container) {
            $weightPercentage = $container->max_weight > 0 
                ? round(($container->current_weight / $container->max_weight) * 100, 2)
                : 0;
            
            $volumePercentage = $container->max_volume > 0
                ? round(($container->current_volume / $container->max_volume) * 100, 2)
                : 0;

            return [
                'container_number' => $container->container_number,
                'container_type' => $container->container_type,
                'status' => $container->status,
                'weight_percentage' => $weightPercentage,
                'volume_percentage' => $volumePercentage,
                'weight_current' => $container->current_weight,
                'weight_max' => $container->max_weight,
                'volume_current' => $container->current_volume,
                'volume_max' => $container->max_volume,
                'items_count' => $container->items()->count(),
            ];
        })->toArray();
    }

    public function getSummary(): array
    {
        if (!$this->shipment) {
            return [];
        }

        $containers = $this->shipment->containers()->get();
        
        $totalMaxWeight = $containers->sum('max_weight');
        $totalCurrentWeight = $containers->sum('current_weight');
        $totalMaxVolume = $containers->sum('max_volume');
        $totalCurrentVolume = $containers->sum('current_volume');

        return [
            'total_containers' => $containers->count(),
            'sealed_containers' => $containers->where('status', 'sealed')->count(),
            'weight_utilization' => $totalMaxWeight > 0 ? round(($totalCurrentWeight / $totalMaxWeight) * 100, 2) : 0,
            'volume_utilization' => $totalMaxVolume > 0 ? round(($totalCurrentVolume / $totalMaxVolume) * 100, 2) : 0,
            'total_weight' => "{$totalCurrentWeight} / {$totalMaxWeight} kg",
            'total_volume' => "{$totalCurrentVolume} / {$totalMaxVolume} m³",
        ];
    }
}
