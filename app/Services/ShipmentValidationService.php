<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\ShipmentContainer;
use App\Models\ProformaInvoiceItem;
use Illuminate\Validation\ValidationException;

class ShipmentValidationService
{
    /**
     * Validate if a container can be sealed
     */
    public function validateContainerSealing(ShipmentContainer $container): bool
    {
        // Validate if container has items
        if ($container->items()->count() === 0) {
            throw ValidationException::withMessages([
                'container' => 'Container must have at least one item to be sealed.',
            ]);
        }

        // Validate if all items have weight and volume
        $invalidItems = $container->items()
            ->where(function ($q) {
                $q->whereNull('total_weight')
                  ->orWhere('total_weight', 0)
                  ->orWhereNull('total_volume')
                  ->orWhere('total_volume', 0);
            })
            ->count();

        if ($invalidItems > 0) {
            throw ValidationException::withMessages([
                'container' => 'All items must have weight and volume defined.',
            ]);
        }

        return true;
    }

    /**
     * Validate if a shipment can be confirmed
     */
    public function validateShipmentConfirmation(Shipment $shipment): bool
    {
        // Validate if shipment has containers
        if ($shipment->containers()->count() === 0) {
            throw ValidationException::withMessages([
                'shipment' => 'Shipment must have at least one container.',
            ]);
        }

        // Validate if all containers are sealed
        $unsealedContainers = $shipment->containers()
            ->where('status', '!=', 'sealed')
            ->count();

        if ($unsealedContainers > 0) {
            throw ValidationException::withMessages([
                'shipment' => 'All containers must be sealed before confirming the shipment.',
            ]);
        }

        // Validate if all ProformaInvoices are fully allocated
        $incompletePIs = $shipment->shipmentInvoices()
            ->where('status', '!=', 'fully_shipped')
            ->count();

        if ($incompletePIs > 0) {
            throw ValidationException::withMessages([
                'shipment' => 'All ProformaInvoices must be fully allocated.',
            ]);
        }

        return true;
    }

    /**
     * Validate if a quantity can be added to a container
     */
    public function validateQuantityAddition(
        ShipmentContainer $container,
        ProformaInvoiceItem $piItem,
        int $quantity
    ): bool {
        // Validate available quantity
        if ($quantity > $piItem->quantity_remaining) {
            throw ValidationException::withMessages([
                'quantity' => "Insufficient quantity. Remaining: {$piItem->quantity_remaining}, Requested: {$quantity}",
            ]);
        }

        // Validate container weight capacity
        $totalWeight = $container->current_weight + ($piItem->unit_weight * $quantity);
        if ($totalWeight > $container->max_weight) {
            throw ValidationException::withMessages([
                'weight' => "Weight exceeded. Maximum: {$container->max_weight}kg, Total: {$totalWeight}kg",
            ]);
        }

        // Validate container volume capacity
        $totalVolume = $container->current_volume + ($piItem->unit_volume * $quantity);
        if ($totalVolume > $container->max_volume) {
            throw ValidationException::withMessages([
                'volume' => "Volume exceeded. Maximum: {$container->max_volume}m³, Total: {$totalVolume}m³",
            ]);
        }

        return true;
    }

    /**
     * Validate if a container can be removed
     */
    public function validateContainerRemoval(ShipmentContainer $container): bool
    {
        // Validate if container is sealed
        if ($container->status === 'sealed') {
            throw ValidationException::withMessages([
                'container' => 'Cannot remove a sealed container.',
            ]);
        }

        // Validate if container is in transit
        if ($container->status === 'in_transit') {
            throw ValidationException::withMessages([
                'container' => 'Cannot remove a container in transit.',
            ]);
        }

        return true;
    }

    /**
     * Calculate container utilization
     */
    public function calculateUtilization(ShipmentContainer $container): array
    {
        $weightUtilization = ($container->current_weight / $container->max_weight) * 100;
        $volumeUtilization = ($container->current_volume / $container->max_volume) * 100;

        return [
            'weight_utilization' => round($weightUtilization, 2),
            'volume_utilization' => round($volumeUtilization, 2),
            'overall_utilization' => round(($weightUtilization + $volumeUtilization) / 2, 2),
            'is_optimized' => $weightUtilization >= 70 && $volumeUtilization >= 70,
        ];
    }

    /**
     * Validate shipment data integrity
     */
    public function validateDataIntegrity(Shipment $shipment): array
    {
        $issues = [];

        // Check for orphan containers
        $orphanContainers = $shipment->containers()
            ->whereDoesntHave('items')
            ->count();

        if ($orphanContainers > 0) {
            $issues[] = "There are {$orphanContainers} containers without items.";
        }

        // Check for duplicate items
        $duplicateItems = $shipment->containers()
            ->with('items')
            ->get()
            ->flatMap(fn($c) => $c->items)
            ->groupBy('proforma_invoice_item_id')
            ->filter(fn($items) => $items->count() > 1)
            ->count();

        if ($duplicateItems > 0) {
            $issues[] = "There are {$duplicateItems} duplicate items across multiple containers.";
        }

        // Check for quantity discrepancies
        foreach ($shipment->shipmentInvoices as $si) {
            $totalAllocated = $shipment->containers()
                ->with('items')
                ->get()
                ->flatMap(fn($c) => $c->items)
                ->where('proforma_invoice_item_id', $si->proforma_invoice_id)
                ->sum('quantity');

            $expected = $si->proformaInvoice->items->sum('quantity');

            if ($totalAllocated !== $expected) {
                $issues[] = "ProformaInvoice #{$si->proforma_invoice_id}: Allocated {$totalAllocated}, Expected {$expected}";
            }
        }

        return $issues;
    }

    /**
     * Check for capacity warnings (before errors)
     * 
     * Returns warnings when utilization is approaching limits
     */
    public function checkCapacityWarnings(ShipmentContainer $container): array
    {
        $warnings = [];
        
        $weightUtilization = ($container->current_weight / $container->max_weight) * 100;
        $volumeUtilization = ($container->current_volume / $container->max_volume) * 100;

        // Warning at 90% capacity
        if ($weightUtilization >= 90 && $weightUtilization < 100) {
            $warnings[] = [
                'type' => 'weight',
                'level' => 'warning',
                'message' => "Weight capacity at {$weightUtilization}% - approaching limit",
                'utilization' => round($weightUtilization, 2),
            ];
        }

        if ($volumeUtilization >= 90 && $volumeUtilization < 100) {
            $warnings[] = [
                'type' => 'volume',
                'level' => 'warning',
                'message' => "Volume capacity at {$volumeUtilization}% - approaching limit",
                'utilization' => round($volumeUtilization, 2),
            ];
        }

        // Critical warning at 95% capacity
        if ($weightUtilization >= 95 && $weightUtilization < 100) {
            $warnings[] = [
                'type' => 'weight',
                'level' => 'critical',
                'message' => "Weight capacity at {$weightUtilization}% - CRITICAL",
                'utilization' => round($weightUtilization, 2),
            ];
        }

        if ($volumeUtilization >= 95 && $volumeUtilization < 100) {
            $warnings[] = [
                'type' => 'volume',
                'level' => 'critical',
                'message' => "Volume capacity at {$volumeUtilization}% - CRITICAL",
                'utilization' => round($volumeUtilization, 2),
            ];
        }

        // Low utilization warning (below 50%)
        if ($weightUtilization < 50 && $container->items()->count() > 0) {
            $warnings[] = [
                'type' => 'weight',
                'level' => 'info',
                'message' => "Weight utilization is low ({$weightUtilization}%) - consider consolidation",
                'utilization' => round($weightUtilization, 2),
            ];
        }

        if ($volumeUtilization < 50 && $container->items()->count() > 0) {
            $warnings[] = [
                'type' => 'volume',
                'level' => 'info',
                'message' => "Volume utilization is low ({$volumeUtilization}%) - consider consolidation",
                'utilization' => round($volumeUtilization, 2),
            ];
        }

        return $warnings;
    }

    /**
     * Validate quantity availability across all items
     */
    public function validateQuantityAvailability(Shipment $shipment): array
    {
        $issues = [];

        foreach ($shipment->items as $item) {
            $piItem = $item->proformaInvoiceItem;
            
            if (!$piItem) {
                $issues[] = "Item #{$item->id} has no linked ProformaInvoice item";
                continue;
            }

            if ($item->quantity_to_ship > $piItem->quantity_remaining) {
                $issues[] = "Item '{$piItem->product_name}': Requested {$item->quantity_to_ship}, Available {$piItem->quantity_remaining}";
            }
        }

        return $issues;
    }

    /**
     * Get comprehensive validation report
     */
    public function getValidationReport(Shipment $shipment): array
    {
        $report = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => [],
            'info' => [],
        ];

        // Check data integrity
        $integrityIssues = $this->validateDataIntegrity($shipment);
        if (!empty($integrityIssues)) {
            $report['errors'] = array_merge($report['errors'], $integrityIssues);
            $report['is_valid'] = false;
        }

        // Check quantity availability
        $quantityIssues = $this->validateQuantityAvailability($shipment);
        if (!empty($quantityIssues)) {
            $report['errors'] = array_merge($report['errors'], $quantityIssues);
            $report['is_valid'] = false;
        }

        // Check container warnings
        foreach ($shipment->containers as $container) {
            $warnings = $this->checkCapacityWarnings($container);
            foreach ($warnings as $warning) {
                if ($warning['level'] === 'critical') {
                    $report['warnings'][] = "Container {$container->container_number}: {$warning['message']}";
                } elseif ($warning['level'] === 'warning') {
                    $report['warnings'][] = "Container {$container->container_number}: {$warning['message']}";
                } else {
                    $report['info'][] = "Container {$container->container_number}: {$warning['message']}";
                }
            }
        }

        return $report;
    }
}
