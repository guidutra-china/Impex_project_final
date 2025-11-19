<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SupplierQuote;
use App\Models\Currency;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    /**
     * Create Purchase Order from Supplier Quote
     *
     * @param SupplierQuote $supplierQuote
     * @param array $additionalData
     * @return PurchaseOrder
     */
    public function createFromQuote(SupplierQuote $supplierQuote, array $additionalData = []): PurchaseOrder
    {
        return DB::transaction(function () use ($supplierQuote, $additionalData) {
            // Get base currency
            $baseCurrency = Currency::where('is_base', true)->firstOrFail();

            // Create PO
            $po = PurchaseOrder::create([
                'po_number' => $this->generatePONumber(),
                'order_id' => $supplierQuote->order_id,
                'supplier_quote_id' => $supplierQuote->id,
                'supplier_id' => $supplierQuote->supplier_id,
                'currency_id' => $supplierQuote->currency_id,
                'exchange_rate' => $supplierQuote->exchange_rate,
                'base_currency_id' => $baseCurrency->id,
                'subtotal' => $supplierQuote->total_price,
                'total' => $supplierQuote->total_price,
                'total_base_currency' => (int) ($supplierQuote->total_price * $supplierQuote->exchange_rate),
                'po_date' => now(),
                'status' => 'draft',
                'created_by' => auth()->id(),
                ...$additionalData,
            ]);

            // Create PO items from quote items
            foreach ($supplierQuote->items as $quoteItem) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $quoteItem->product_id,
                    'quantity' => $quoteItem->quantity,
                    'unit_cost' => $quoteItem->unit_price,
                    'total_cost' => $quoteItem->total_price,
                    'product_name' => $quoteItem->product->name,
                    'product_sku' => $quoteItem->product->sku,
                ]);
            }

            return $po->load('items');
        });
    }

    /**
     * Create Purchase Order manually (without quote)
     *
     * @param array $data
     * @param array $items
     * @return PurchaseOrder
     */
    public function createManual(array $data, array $items): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $items) {
            // Get base currency
            $baseCurrency = Currency::where('is_base', true)->firstOrFail();

            // Calculate totals
            $subtotal = 0;
            foreach ($items as $item) {
                $subtotal += $item['quantity'] * $item['unit_cost'];
            }

            $total = $subtotal + ($data['shipping_cost'] ?? 0) + ($data['insurance_cost'] ?? 0) 
                   + ($data['other_costs'] ?? 0) + ($data['tax'] ?? 0) - ($data['discount'] ?? 0);

            // Create PO
            $po = PurchaseOrder::create([
                'po_number' => $this->generatePONumber(),
                'supplier_id' => $data['supplier_id'],
                'currency_id' => $data['currency_id'],
                'exchange_rate' => $data['exchange_rate'],
                'base_currency_id' => $baseCurrency->id,
                'subtotal' => $subtotal,
                'shipping_cost' => $data['shipping_cost'] ?? 0,
                'insurance_cost' => $data['insurance_cost'] ?? 0,
                'other_costs' => $data['other_costs'] ?? 0,
                'tax' => $data['tax'] ?? 0,
                'discount' => $data['discount'] ?? 0,
                'total' => $total,
                'total_base_currency' => (int) ($total * $data['exchange_rate']),
                'po_date' => $data['po_date'] ?? now(),
                'status' => 'draft',
                'created_by' => auth()->id(),
                ...$data,
            ]);

            // Create PO items
            foreach ($items as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    ...$item,
                ]);
            }

            return $po->load('items');
        });
    }

    /**
     * Update Purchase Order
     *
     * @param PurchaseOrder $po
     * @param array $data
     * @param array|null $items
     * @return PurchaseOrder
     */
    public function update(PurchaseOrder $po, array $data, ?array $items = null): PurchaseOrder
    {
        return DB::transaction(function () use ($po, $data, $items) {
            // Update PO
            $po->update($data);

            // Update items if provided
            if ($items !== null) {
                // Delete existing items
                $po->items()->delete();

                // Create new items
                foreach ($items as $item) {
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        ...$item,
                    ]);
                }

                // Recalculate totals
                $this->recalculateTotals($po);
            }

            return $po->fresh('items');
        });
    }

    /**
     * Approve Purchase Order
     *
     * @param PurchaseOrder $po
     * @return PurchaseOrder
     */
    public function approve(PurchaseOrder $po): PurchaseOrder
    {
        $po->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
        ]);

        return $po;
    }

    /**
     * Send Purchase Order to supplier
     *
     * @param PurchaseOrder $po
     * @return PurchaseOrder
     */
    public function send(PurchaseOrder $po): PurchaseOrder
    {
        $po->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        // TODO: Send email to supplier

        return $po;
    }

    /**
     * Mark Purchase Order as confirmed by supplier
     *
     * @param PurchaseOrder $po
     * @return PurchaseOrder
     */
    public function confirm(PurchaseOrder $po): PurchaseOrder
    {
        $po->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        return $po;
    }

    /**
     * Cancel Purchase Order
     *
     * @param PurchaseOrder $po
     * @param string|null $reason
     * @return PurchaseOrder
     */
    public function cancel(PurchaseOrder $po, ?string $reason = null): PurchaseOrder
    {
        $po->update([
            'status' => 'cancelled',
            'notes' => $po->notes . "\n\nCancellation reason: " . $reason,
        ]);

        return $po;
    }

    /**
     * Recalculate PO totals based on items
     *
     * @param PurchaseOrder $po
     * @return void
     */
    private function recalculateTotals(PurchaseOrder $po): void
    {
        $subtotal = $po->items()->sum('total_cost');
        
        $total = $subtotal + $po->shipping_cost + $po->insurance_cost 
               + $po->other_costs + $po->tax - $po->discount;

        $po->update([
            'subtotal' => $subtotal,
            'total' => $total,
            'total_base_currency' => (int) ($total * $po->exchange_rate),
        ]);
    }

    /**
     * Generate unique PO number
     *
     * @return string
     */
    private function generatePONumber(): string
    {
        $year = date('Y');
        $lastPO = PurchaseOrder::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastPO ? (int) substr($lastPO->po_number, -4) + 1 : 1;

        return sprintf('PO-%s-%04d', $year, $nextNumber);
    }
}
