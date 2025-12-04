<?php

namespace App\Services;

use App\Models\ShipmentContainer;
use App\Models\ShipmentContainerItem;
use App\Models\ProformaInvoiceItem;
use Exception;

class ShipmentContainerService
{
    /**
     * Adicionar item ao container com validações
     */
    public function addItemToContainer(
        ShipmentContainer $container,
        ProformaInvoiceItem $piItem,
        int $quantity
    ): ShipmentContainerItem {
        // V1: Validar quantidade disponível
        if (!$piItem->canShip($quantity)) {
            throw new Exception(
                "Quantidade insuficiente para {$piItem->product_name}. " .
                "Restante: {$piItem->getQuantityRemaining()}, " .
                "Solicitado: {$quantity}"
            );
        }

        // V2: Validar capacidade do container
        $totalWeight = $quantity * $piItem->product->weight;
        $totalVolume = $quantity * $piItem->product->volume;

        if (!$container->canFit($totalWeight, $totalVolume)) {
            throw new Exception(
                "Container {$container->container_number} sem capacidade. " .
                "Peso: {$totalWeight}kg / {$container->getRemainingWeight()}kg, " .
                "Volume: {$totalVolume}m³ / {$container->getRemainingVolume()}m³"
            );
        }

        // V3: Validar que PI está neste shipment
        if (!$container->shipment->shipmentInvoices()
            ->where('proforma_invoice_id', $piItem->proforma_invoice_id)
            ->exists()) {
            throw new Exception(
                "ProformaInvoice {$piItem->proforma_invoice_id} não está neste shipment"
            );
        }

        // V4: Validar status do shipment
        if (!in_array($container->shipment->status, ['draft', 'preparing'])) {
            throw new Exception(
                "Shipment {$container->shipment->shipment_number} não permite adições (status: {$container->shipment->status})"
            );
        }

        // V5: Validar status do container
        if (!in_array($container->status, ['draft', 'packed'])) {
            throw new Exception(
                "Container {$container->container_number} não permite adições (status: {$container->status})"
            );
        }

        // Criar item
        return ShipmentContainerItem::create([
            'shipment_container_id' => $container->id,
            'proforma_invoice_item_id' => $piItem->id,
            'product_id' => $piItem->product_id,
            'quantity' => $quantity,
            'unit_weight' => $piItem->product->weight,
            'total_weight' => $totalWeight,
            'unit_volume' => $piItem->product->volume,
            'total_volume' => $totalVolume,
            'unit_price' => $piItem->unit_price,
            'customs_value' => $quantity * $piItem->unit_price,
            'shipment_sequence' => $piItem->shipment_count + 1,
            'status' => 'draft',
        ]);
    }

    /**
     * Remover item do container com validações
     */
    public function removeItemFromContainer(ShipmentContainerItem $item): void
    {
        // V1: Validar status do shipment
        if (!in_array($item->container->shipment->status, ['draft', 'preparing'])) {
            throw new Exception(
                "Não pode remover itens de shipment em status {$item->container->shipment->status}"
            );
        }

        // V2: Validar status do container
        if (!in_array($item->container->status, ['draft', 'packed'])) {
            throw new Exception(
                "Não pode remover itens de container em status {$item->container->status}"
            );
        }

        // Remover (os hooks vão decrementar quantity_shipped)
        $item->delete();
    }

    /**
     * Selar container
     */
    public function sealContainer(ShipmentContainer $container, string $sealNumber, int $userId): void
    {
        // V1: Validar que container tem itens
        if ($container->items()->count() === 0) {
            throw new Exception("Container não pode estar vazio");
        }

        // V2: Validar que todos os itens estão packed
        if ($container->items()->where('status', '!=', 'packed')->exists()) {
            throw new Exception("Nem todos os itens estão packed");
        }

        // V3: Validar que seal_number é único
        if (ShipmentContainer::where('seal_number', $sealNumber)
            ->where('id', '!=', $container->id)
            ->exists()) {
            throw new Exception("Seal number {$sealNumber} já está em uso");
        }

        $container->seal($sealNumber, $userId);
    }

    /**
     * Desselar container
     */
    public function unsealContainer(ShipmentContainer $container): void
    {
        // V1: Validar que shipment não foi confirmado
        if ($container->shipment->status !== 'preparing') {
            throw new Exception(
                "Não pode desselar container de shipment em status {$container->shipment->status}"
            );
        }

        $container->unseal();
    }

    /**
     * Calcular totais do container
     */
    public function calculateContainerTotals(ShipmentContainer $container): void
    {
        $container->calculateTotals();
    }

    /**
     * Obter resumo do container
     */
    public function getContainerSummary(ShipmentContainer $container): array
    {
        $items = $container->items()->with('product', 'proformaInvoiceItem')->get();

        return [
            'container_number' => $container->container_number,
            'container_type' => $container->container_type,
            'status' => $container->status,
            'seal_number' => $container->seal_number,
            'items_count' => $items->count(),
            'total_quantity' => $items->sum('quantity'),
            'weight' => [
                'current' => $container->current_weight,
                'max' => $container->max_weight,
                'remaining' => $container->getRemainingWeight(),
                'utilization' => round($container->getWeightUtilization(), 2) . '%',
            ],
            'volume' => [
                'current' => $container->current_volume,
                'max' => $container->max_volume,
                'remaining' => $container->getRemainingVolume(),
                'utilization' => round($container->getVolumeUtilization(), 2) . '%',
            ],
            'proforma_invoices' => $container->getProformaInvoicesInContainer(),
        ];
    }
}
