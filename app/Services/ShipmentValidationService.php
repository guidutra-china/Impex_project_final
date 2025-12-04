<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\ShipmentContainer;
use App\Models\ProformaInvoiceItem;
use Illuminate\Validation\ValidationException;

class ShipmentValidationService
{
    /**
     * Validar se um container pode ser selado
     */
    public function validateContainerSealing(ShipmentContainer $container): bool
    {
        // Validar se container tem itens
        if ($container->items()->count() === 0) {
            throw ValidationException::withMessages([
                'container' => 'Container deve ter pelo menos um item para ser selado.',
            ]);
        }

        // Validar se todos os itens têm peso e volume
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
                'container' => 'Todos os itens devem ter peso e volume definidos.',
            ]);
        }

        return true;
    }

    /**
     * Validar se um shipment pode ser confirmado
     */
    public function validateShipmentConfirmation(Shipment $shipment): bool
    {
        // Validar se shipment tem containers
        if ($shipment->containers()->count() === 0) {
            throw ValidationException::withMessages([
                'shipment' => 'Shipment deve ter pelo menos um container.',
            ]);
        }

        // Validar se todos os containers estão selados
        $unsealedContainers = $shipment->containers()
            ->where('status', '!=', 'sealed')
            ->count();

        if ($unsealedContainers > 0) {
            throw ValidationException::withMessages([
                'shipment' => 'Todos os containers devem estar selados antes de confirmar o shipment.',
            ]);
        }

        // Validar se todas as ProformaInvoices estão totalmente alocadas
        $incompletePIs = $shipment->shipmentInvoices()
            ->where('status', '!=', 'fully_shipped')
            ->count();

        if ($incompletePIs > 0) {
            throw ValidationException::withMessages([
                'shipment' => 'Todas as ProformaInvoices devem estar totalmente alocadas.',
            ]);
        }

        return true;
    }

    /**
     * Validar se uma quantidade pode ser adicionada a um container
     */
    public function validateQuantityAddition(
        ShipmentContainer $container,
        ProformaInvoiceItem $piItem,
        int $quantity
    ): bool {
        // Validar quantidade disponível
        if ($quantity > $piItem->quantity_remaining) {
            throw ValidationException::withMessages([
                'quantity' => "Quantidade insuficiente. Restante: {$piItem->quantity_remaining}, Solicitado: {$quantity}",
            ]);
        }

        // Validar capacidade do container
        $totalWeight = $container->current_weight + ($piItem->unit_weight * $quantity);
        if ($totalWeight > $container->max_weight) {
            throw ValidationException::withMessages([
                'weight' => "Peso excedido. Máximo: {$container->max_weight}kg, Total: {$totalWeight}kg",
            ]);
        }

        $totalVolume = $container->current_volume + ($piItem->unit_volume * $quantity);
        if ($totalVolume > $container->max_volume) {
            throw ValidationException::withMessages([
                'volume' => "Volume excedido. Máximo: {$container->max_volume}m³, Total: {$totalVolume}m³",
            ]);
        }

        return true;
    }

    /**
     * Validar se um container pode ser removido
     */
    public function validateContainerRemoval(ShipmentContainer $container): bool
    {
        // Validar se container está selado
        if ($container->status === 'sealed') {
            throw ValidationException::withMessages([
                'container' => 'Não é possível remover um container selado.',
            ]);
        }

        // Validar se container está em trânsito
        if ($container->status === 'in_transit') {
            throw ValidationException::withMessages([
                'container' => 'Não é possível remover um container em trânsito.',
            ]);
        }

        return true;
    }

    /**
     * Calcular utilização de container
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
     * Validar integridade de dados do shipment
     */
    public function validateDataIntegrity(Shipment $shipment): array
    {
        $issues = [];

        // Verificar se há containers órfãos
        $orphanContainers = $shipment->containers()
            ->whereDoesntHave('items')
            ->count();

        if ($orphanContainers > 0) {
            $issues[] = "Existem {$orphanContainers} containers sem itens.";
        }

        // Verificar se há itens duplicados
        $duplicateItems = $shipment->containers()
            ->with('items')
            ->get()
            ->flatMap(fn($c) => $c->items)
            ->groupBy('proforma_invoice_item_id')
            ->filter(fn($items) => $items->count() > 1)
            ->count();

        if ($duplicateItems > 0) {
            $issues[] = "Existem {$duplicateItems} itens duplicados em múltiplos containers.";
        }

        // Verificar se há discrepâncias de quantidade
        foreach ($shipment->shipmentInvoices as $si) {
            $totalAllocated = $shipment->containers()
                ->with('items')
                ->get()
                ->flatMap(fn($c) => $c->items)
                ->where('proforma_invoice_item_id', $si->proforma_invoice_id)
                ->sum('quantity');

            $expected = $si->proformaInvoice->items->sum('quantity');

            if ($totalAllocated !== $expected) {
                $issues[] = "ProformaInvoice #{$si->proforma_invoice_id}: Alocado {$totalAllocated}, Esperado {$expected}";
            }
        }

        return $issues;
    }
}
