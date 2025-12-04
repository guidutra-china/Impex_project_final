<?php

namespace App\Reports;

use App\Models\Shipment;
use App\Models\ShipmentContainer;
use Illuminate\Support\Collection;

class ShipmentContainerReport
{
    public function __construct(
        private Shipment $shipment
    ) {}

    /**
     * Get comprehensive container report for a shipment
     */
    public function generate(): array
    {
        $containers = $this->shipment->containers()->with('items.proformaInvoiceItem')->get();

        return [
            'shipment' => [
                'id' => $this->shipment->id,
                'shipment_number' => $this->shipment->shipment_number,
                'status' => $this->shipment->status,
                'created_at' => $this->shipment->created_at,
            ],
            'summary' => $this->generateSummary($containers),
            'containers' => $this->generateContainerDetails($containers),
            'proforma_invoices' => $this->generateProformaInvoiceSummary($containers),
            'utilization' => $this->calculateUtilization($containers),
        ];
    }

    /**
     * Generate summary statistics
     */
    private function generateSummary(Collection $containers): array
    {
        return [
            'total_containers' => $containers->count(),
            'sealed_containers' => $containers->where('status', 'sealed')->count(),
            'total_weight' => $containers->sum('current_weight'),
            'total_volume' => $containers->sum('current_volume'),
            'total_items' => $containers->sum(fn($c) => $c->items->count()),
            'total_proforma_invoices' => $containers->flatMap(fn($c) => $c->items->pluck('proforma_invoice_item.proforma_invoice_id'))->unique()->count(),
        ];
    }

    /**
     * Generate detailed container information
     */
    private function generateContainerDetails(Collection $containers): array
    {
        return $containers->map(function (ShipmentContainer $container) {
            return [
                'container_number' => $container->container_number,
                'container_type' => $container->container_type,
                'status' => $container->status,
                'seal_number' => $container->seal_number,
                'sealed_at' => $container->sealed_at,
                'weight' => [
                    'current' => $container->current_weight,
                    'max' => $container->max_weight,
                    'percentage' => round(($container->current_weight / $container->max_weight) * 100, 2),
                ],
                'volume' => [
                    'current' => $container->current_volume,
                    'max' => $container->max_volume,
                    'percentage' => round(($container->current_volume / $container->max_volume) * 100, 2),
                ],
                'items' => $container->items->map(function ($item) {
                    return [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product?->name,
                        'quantity' => $item->quantity,
                        'proforma_invoice_id' => $item->proformaInvoiceItem?->proforma_invoice_id,
                    ];
                })->toArray(),
            ];
        })->toArray();
    }

    /**
     * Generate ProformaInvoice summary
     */
    private function generateProformaInvoiceSummary(Collection $containers): array
    {
        $proformaData = [];

        foreach ($containers as $container) {
            foreach ($container->items as $item) {
                $piId = $item->proformaInvoiceItem?->proforma_invoice_id;
                
                if (!isset($proformaData[$piId])) {
                    $proformaData[$piId] = [
                        'proforma_invoice_id' => $piId,
                        'total_quantity' => 0,
                        'shipped_quantity' => 0,
                        'containers' => [],
                    ];
                }

                $proformaData[$piId]['shipped_quantity'] += $item->quantity;
                $proformaData[$piId]['containers'][] = $container->container_number;
            }
        }

        return array_values($proformaData);
    }

    /**
     * Calculate container utilization
     */
    private function calculateUtilization(Collection $containers): array
    {
        $totalMaxWeight = $containers->sum('max_weight');
        $totalMaxVolume = $containers->sum('max_volume');
        $totalCurrentWeight = $containers->sum('current_weight');
        $totalCurrentVolume = $containers->sum('current_volume');

        return [
            'weight_utilization' => $totalMaxWeight > 0 ? round(($totalCurrentWeight / $totalMaxWeight) * 100, 2) : 0,
            'volume_utilization' => $totalMaxVolume > 0 ? round(($totalCurrentVolume / $totalMaxVolume) * 100, 2) : 0,
            'average_weight_utilization' => $containers->count() > 0 
                ? round($containers->average(fn($c) => $c->max_weight > 0 ? ($c->current_weight / $c->max_weight) * 100 : 0), 2)
                : 0,
            'average_volume_utilization' => $containers->count() > 0
                ? round($containers->average(fn($c) => $c->max_volume > 0 ? ($c->current_volume / $c->max_volume) * 100 : 0), 2)
                : 0,
        ];
    }

    /**
     * Export report as array
     */
    public function toArray(): array
    {
        return $this->generate();
    }

    /**
     * Export report as JSON
     */
    public function toJson(): string
    {
        return json_encode($this->generate(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
