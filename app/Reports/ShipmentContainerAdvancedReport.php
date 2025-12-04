<?php

namespace App\Reports;

use App\Models\Shipment;
use App\Models\ShipmentContainer;
use Illuminate\Support\Collection;

class ShipmentContainerAdvancedReport
{
    /**
     * Gerar relatório de utilização de containers
     */
    public static function utilizationReport(Shipment $shipment): array
    {
        $containers = $shipment->containers()->with('items')->get();

        $report = [
            'shipment_number' => $shipment->shipment_number,
            'total_containers' => $containers->count(),
            'containers' => [],
            'summary' => [],
        ];

        $totalWeight = 0;
        $totalVolume = 0;
        $totalMaxWeight = 0;
        $totalMaxVolume = 0;

        foreach ($containers as $container) {
            $weightUtilization = ($container->current_weight / $container->max_weight) * 100;
            $volumeUtilization = ($container->current_volume / $container->max_volume) * 100;

            $report['containers'][] = [
                'container_number' => $container->container_number,
                'container_type' => $container->container_type,
                'status' => $container->status,
                'current_weight' => $container->current_weight,
                'max_weight' => $container->max_weight,
                'weight_utilization' => round($weightUtilization, 2) . '%',
                'current_volume' => $container->current_volume,
                'max_volume' => $container->max_volume,
                'volume_utilization' => round($volumeUtilization, 2) . '%',
                'items_count' => $container->items()->count(),
                'sealed_at' => $container->sealed_at,
            ];

            $totalWeight += $container->current_weight;
            $totalVolume += $container->current_volume;
            $totalMaxWeight += $container->max_weight;
            $totalMaxVolume += $container->max_volume;
        }

        $report['summary'] = [
            'total_weight' => $totalWeight,
            'total_max_weight' => $totalMaxWeight,
            'weight_utilization' => round(($totalWeight / $totalMaxWeight) * 100, 2) . '%',
            'total_volume' => $totalVolume,
            'total_max_volume' => $totalMaxVolume,
            'volume_utilization' => round(($totalVolume / $totalMaxVolume) * 100, 2) . '%',
            'average_container_utilization' => round(
                (($totalWeight / $totalMaxWeight) + ($totalVolume / $totalMaxVolume)) / 2 * 100,
                2
            ) . '%',
        ];

        return $report;
    }

    /**
     * Gerar relatório de distribuição por ProformaInvoice
     */
    public static function distributionByProformaInvoice(Shipment $shipment): array
    {
        $report = [
            'shipment_number' => $shipment->shipment_number,
            'proforma_invoices' => [],
        ];

        foreach ($shipment->shipmentInvoices as $si) {
            $items = $shipment->containers()
                ->with('items')
                ->get()
                ->flatMap(fn($c) => $c->items)
                ->where('proforma_invoice_item_id', $si->proforma_invoice_id);

            $report['proforma_invoices'][] = [
                'proforma_number' => $si->proformaInvoice->proforma_number,
                'status' => $si->status,
                'total_items' => $si->proformaInvoice->items()->count(),
                'allocated_items' => $items->count(),
                'total_quantity' => $si->proformaInvoice->items()->sum('quantity'),
                'allocated_quantity' => $items->sum('quantity'),
                'containers' => $items->pluck('shipment_container_id')->unique()->count(),
                'shipment_sequence' => $items->pluck('shipment_sequence')->unique()->values()->toArray(),
            ];
        }

        return $report;
    }

    /**
     * Gerar relatório de histórico de shipments
     */
    public static function shipmentHistoryReport(Shipment $shipment): array
    {
        $containers = $shipment->containers()->orderBy('created_at')->get();

        $report = [
            'shipment_number' => $shipment->shipment_number,
            'created_at' => $shipment->created_at,
            'status' => $shipment->status,
            'timeline' => [],
        ];

        foreach ($containers as $container) {
            $report['timeline'][] = [
                'container_number' => $container->container_number,
                'created_at' => $container->created_at,
                'sealed_at' => $container->sealed_at,
                'status' => $container->status,
                'items_count' => $container->items()->count(),
                'total_weight' => $container->current_weight,
                'total_volume' => $container->current_volume,
            ];
        }

        return $report;
    }

    /**
     * Gerar relatório de otimização de containers
     */
    public static function optimizationReport(Shipment $shipment): array
    {
        $containers = $shipment->containers()->with('items')->get();

        $optimized = [];
        $underutilized = [];
        $overutilized = [];

        foreach ($containers as $container) {
            $weightUtil = ($container->current_weight / $container->max_weight) * 100;
            $volumeUtil = ($container->current_volume / $container->max_volume) * 100;
            $avgUtil = ($weightUtil + $volumeUtil) / 2;

            $data = [
                'container_number' => $container->container_number,
                'weight_utilization' => round($weightUtil, 2),
                'volume_utilization' => round($volumeUtil, 2),
                'average_utilization' => round($avgUtil, 2),
            ];

            if ($avgUtil >= 70) {
                $optimized[] = $data;
            } elseif ($avgUtil < 50) {
                $underutilized[] = $data;
            } else {
                $overutilized[] = $data;
            }
        }

        return [
            'shipment_number' => $shipment->shipment_number,
            'optimized_containers' => [
                'count' => count($optimized),
                'percentage' => round((count($optimized) / $containers->count()) * 100, 2),
                'containers' => $optimized,
            ],
            'underutilized_containers' => [
                'count' => count($underutilized),
                'percentage' => round((count($underutilized) / $containers->count()) * 100, 2),
                'containers' => $underutilized,
            ],
            'overutilized_containers' => [
                'count' => count($overutilized),
                'percentage' => round((count($overutilized) / $containers->count()) * 100, 2),
                'containers' => $overutilized,
            ],
        ];
    }

    /**
     * Gerar relatório de custos de container
     */
    public static function costReport(Shipment $shipment): array
    {
        $containers = $shipment->containers()->with('items')->get();

        $report = [
            'shipment_number' => $shipment->shipment_number,
            'containers' => [],
            'summary' => [],
        ];

        $totalCost = 0;
        $totalValue = 0;

        foreach ($containers as $container) {
            $containerCost = $this->estimateContainerCost($container);
            $containerValue = $container->items()->sum('customs_value');

            $report['containers'][] = [
                'container_number' => $container->container_number,
                'container_type' => $container->container_type,
                'estimated_cost' => $containerCost,
                'cargo_value' => $containerValue,
                'cost_per_unit' => $containerValue > 0 ? round($containerCost / $containerValue * 100, 2) . '%' : 'N/A',
                'items_count' => $container->items()->count(),
            ];

            $totalCost += $containerCost;
            $totalValue += $containerValue;
        }

        $report['summary'] = [
            'total_containers' => $containers->count(),
            'total_estimated_cost' => $totalCost,
            'total_cargo_value' => $totalValue,
            'cost_percentage' => $totalValue > 0 ? round(($totalCost / $totalValue) * 100, 2) . '%' : 'N/A',
        ];

        return $report;
    }

    /**
     * Estimar custo do container baseado no tipo e peso
     */
    private static function estimateContainerCost(ShipmentContainer $container): float
    {
        $baseCosts = [
            '20ft' => 1000,
            '40ft' => 1500,
            '40hc' => 1700,
            'pallet' => 100,
            'box' => 50,
        ];

        $baseCost = $baseCosts[$container->container_type] ?? 1000;
        $weightSurcharge = ($container->current_weight / 1000) * 10; // $10 por tonelada

        return $baseCost + $weightSurcharge;
    }
}
