<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\ShipmentInvoice;
use Exception;

class ShipmentService
{
    /**
     * Confirmar shipment com validações
     */
    public function confirmShipment(Shipment $shipment, int $userId): void
    {
        // V1: Validar que todos containers estão selados
        $unsealedContainers = $shipment->containers()
            ->where('status', '!=', 'sealed')
            ->count();

        if ($unsealedContainers > 0) {
            throw new Exception("Existem {$unsealedContainers} containers não selados");
        }

        // V2: Validar quantidade de cada PI
        foreach ($shipment->shipmentInvoices as $si) {
            if (!$si->proforma_invoice_id) {
                continue;
            }

            $piItems = $si->proformaInvoice->items;

            foreach ($piItems as $piItem) {
                // Quantidade alocada NESTE shipment
                $allocatedInThisShipment = $shipment->containers()
                    ->whereHas('items', fn($q) => 
                        $q->where('proforma_invoice_item_id', $piItem->id)
                            ->where('shipment_sequence', $piItem->shipment_count)
                    )
                    ->sum('quantity');

                $remaining = $piItem->getQuantityRemaining();

                // Se foi alocado, validar
                if ($allocatedInThisShipment > 0) {
                    if ($allocatedInThisShipment > $remaining) {
                        throw new Exception(
                            "Item {$piItem->product_name}: Quantidade alocada ({$allocatedInThisShipment}) > restante ({$remaining})"
                        );
                    }
                }
            }
        }

        // V3: Validar que não há itens órfãos
        $unallocatedItems = $shipment->items()
            ->whereNull('container_id')
            ->count();

        if ($unallocatedItems > 0) {
            throw new Exception("Existem {$unallocatedItems} itens não alocados a containers");
        }

        // V4: Atualizar status de cada ShipmentInvoice
        foreach ($shipment->shipmentInvoices as $si) {
            if ($si->proforma_invoice_id) {
                $piTotalQuantity = $si->proformaInvoice->items()->sum('quantity');
                $piShippedQuantity = $si->proformaInvoice->items()
                    ->sum('quantity_shipped');

                if ($piShippedQuantity >= $piTotalQuantity) {
                    $si->status = 'fully_shipped';
                } else {
                    $si->status = 'partial_shipped';
                }
                $si->shipped_at = now();
                $si->save();
            }
        }

        // Confirmar shipment
        $shipment->status = 'confirmed';
        $shipment->confirmed_at = now();
        $shipment->confirmed_by = $userId;
        $shipment->save();
    }

    /**
     * Cancelar shipment
     */
    public function cancelShipment(Shipment $shipment, string $reason = null): void
    {
        // V1: Validar status
        if (!in_array($shipment->status, ['draft', 'preparing'])) {
            throw new Exception(
                "Não pode cancelar shipment em status {$shipment->status}"
            );
        }

        // V2: Remover todas as alocações
        foreach ($shipment->containers()->get() as $container) {
            foreach ($container->items()->get() as $item) {
                $item->delete();
            }
            $container->delete();
        }

        // V3: Cancelar shipment
        $shipment->status = 'cancelled';
        $shipment->notes = ($shipment->notes ?? '') . "\n\nCancelado: {$reason}";
        $shipment->save();
    }

    /**
     * Obter resumo do shipment
     */
    public function getShipmentSummary(Shipment $shipment): array
    {
        $containers = $shipment->containers()->get();
        $invoices = $shipment->shipmentInvoices()->get();

        $totalQuantity = 0;
        $totalWeight = 0;
        $totalVolume = 0;

        foreach ($containers as $container) {
            $totalQuantity += $container->items()->sum('quantity');
            $totalWeight += $container->current_weight;
            $totalVolume += $container->current_volume;
        }

        return [
            'shipment_number' => $shipment->shipment_number,
            'status' => $shipment->status,
            'containers_count' => $containers->count(),
            'proforma_invoices_count' => $invoices->count(),
            'total_quantity' => $totalQuantity,
            'total_weight' => $totalWeight,
            'total_volume' => $totalVolume,
            'containers' => $containers->map(fn($c) => [
                'container_number' => $c->container_number,
                'status' => $c->status,
                'items_count' => $c->items()->count(),
                'weight' => $c->current_weight,
                'volume' => $c->current_volume,
            ]),
            'invoices' => $invoices->map(fn($i) => [
                'proforma_number' => $i->proformaInvoice?->proforma_number,
                'status' => $i->status,
                'total_quantity' => $i->total_quantity,
                'percentage' => round($i->getShippingPercentage(), 2) . '%',
            ]),
        ];
    }

    /**
     * Validar se shipment pode ser confirmado
     */
    public function canConfirmShipment(Shipment $shipment): array
    {
        $errors = [];

        // Validar containers
        $unsealedContainers = $shipment->containers()
            ->where('status', '!=', 'sealed')
            ->count();

        if ($unsealedContainers > 0) {
            $errors[] = "Existem {$unsealedContainers} containers não selados";
        }

        // Validar itens órfãos
        $unallocatedItems = $shipment->items()
            ->whereNull('container_id')
            ->count();

        if ($unallocatedItems > 0) {
            $errors[] = "Existem {$unallocatedItems} itens não alocados";
        }

        // Validar PIs
        foreach ($shipment->shipmentInvoices as $si) {
            if (!$si->proforma_invoice_id) {
                continue;
            }

            $piItems = $si->proformaInvoice->items;

            foreach ($piItems as $piItem) {
                $allocatedInThisShipment = $shipment->containers()
                    ->whereHas('items', fn($q) => 
                        $q->where('proforma_invoice_item_id', $piItem->id)
                            ->where('shipment_sequence', $piItem->shipment_count)
                    )
                    ->sum('quantity');

                $remaining = $piItem->getQuantityRemaining();

                if ($allocatedInThisShipment > 0 && $allocatedInThisShipment > $remaining) {
                    $errors[] = "Item {$piItem->product_name}: Quantidade alocada > restante";
                }
            }
        }

        return [
            'can_confirm' => count($errors) === 0,
            'errors' => $errors,
        ];
    }
}
