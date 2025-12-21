<?php

namespace App\Services;

use App\Models\CustomerQuote;
use App\Models\ProformaInvoice;
use App\Models\ProformaInvoiceItem;
use App\Models\QuoteItem;
use Illuminate\Support\Facades\DB;

class ProformaInvoiceService
{
    /**
     * Create or update Proforma Invoice from Customer Quote product selections
     * If PI already exists for this CustomerQuote, it will be updated with new revision
     *
     * @param CustomerQuote $customerQuote
     * @param array $selectedQuoteItemIds
     * @return ProformaInvoice
     */
    public function createFromCustomerQuoteSelection(CustomerQuote $customerQuote, array $selectedQuoteItemIds): ProformaInvoice
    {
        return DB::transaction(function () use ($customerQuote, $selectedQuoteItemIds) {
            // Get the order with customer
            $order = $customerQuote->order;
            
            if (!$order) {
                throw new \Exception('CustomerQuote does not have an associated Order');
            }
            
            // Load customer if not already loaded
            if (!$order->relationLoaded('customer')) {
                $order->load('customer');
            }
            
            // Get customer_id from order
            $customerId = $order->customer_id ?? $order->client_id ?? null;
            
            if (!$customerId) {
                \Log::error('ProformaInvoice creation failed: No customer_id', [
                    'order_id' => $order->id,
                    'order_customer_id' => $order->customer_id,
                    'customer_quote_id' => $customerQuote->id,
                ]);
                throw new \Exception('Order #' . $order->id . ' does not have an associated Customer. Please assign a customer to the order first.');
            }

            // Check if PI already exists for this CustomerQuote
            $proformaInvoice = ProformaInvoice::where('customer_quote_id', $customerQuote->id)->first();
            
            if ($proformaInvoice) {
                // Update existing PI - increment revision
                $revisionNumber = $proformaInvoice->revision_number + 1;
                
                $proformaInvoice->update([
                    'revision_number' => $revisionNumber,
                    'status' => 'draft', // Reset to draft when revised
                    'notes' => 'Generated from Customer Quote: ' . $customerQuote->quote_number . ' (Revision ' . $revisionNumber . ')',
                    'updated_at' => now(),
                ]);
                
                // Delete old items
                $proformaInvoice->items()->delete();
                
            } else {
                // Create new PI
                $revisionNumber = 1;
                
                $proformaInvoice = ProformaInvoice::create([
                    'order_id' => $order->id,
                    'customer_quote_id' => $customerQuote->id,
                    'customer_id' => $customerId,
                    'public_token' => \Str::random(32),
                    'revision_number' => $revisionNumber,
                    'status' => 'draft',
                    'issue_date' => now(),
                    'valid_until' => now()->addDays(30),
                    'due_date' => now()->addDays(30),
                    'subtotal' => 0,
                    'tax' => 0,
                    'total' => 0,
                    'exchange_rate' => 1.00,
                    'currency_id' => $order->currency_id ?? 1,
                    'rejection_reason' => '',
                    'deposit_required' => false,
                    'deposit_received' => false,
                    'notes' => 'Generated from Customer Quote: ' . $customerQuote->quote_number . ' (Revision ' . $revisionNumber . ')',
                    'terms_and_conditions' => '',
                    'customer_notes' => '',
                    'created_by' => auth()->id() ?? $order->user_id ?? null,
                ]);
            }

            // Get selected quote items
            $quoteItems = QuoteItem::whereIn('id', $selectedQuoteItemIds)
                ->with(['product', 'supplierQuote.supplier'])
                ->get();
                
            if ($quoteItems->isEmpty()) {
                throw new \Exception('No quote items found for the selected IDs');
            }

            $subtotal = 0;

            // Create Proforma Invoice Items
            foreach ($quoteItems as $quoteItem) {
                // Convert cents to dollars for ProformaInvoiceItem (it has mutator that multiplies by 100)
                $unitPriceDollars = $quoteItem->unit_price_after_commission / 100;
                $commissionAmountDollars = ($quoteItem->commission_amount ?? 0) / 100;
                $itemTotalDollars = $unitPriceDollars * $quoteItem->quantity;
                $subtotal += $itemTotalDollars;

                // Conditionally include supplier name based on CustomerQuote setting
                $notes = null;
                if ($customerQuote->show_supplier_names && $quoteItem->supplierQuote && $quoteItem->supplierQuote->supplier) {
                    $notes = 'From Supplier: ' . $quoteItem->supplierQuote->supplier->name;
                }

                ProformaInvoiceItem::create([
                    'proforma_invoice_id' => $proformaInvoice->id,
                    'supplier_quote_id' => $quoteItem->supplier_quote_id,
                    'quote_item_id' => $quoteItem->id,
                    'product_id' => $quoteItem->product_id,
                    'product_name' => $quoteItem->product->name ?? 'Product',
                    'product_sku' => $quoteItem->product->code ?? null,
                    'quantity' => $quoteItem->quantity,
                    'quantity_shipped' => 0,
                    'quantity_remaining' => $quoteItem->quantity,
                    'shipment_count' => 0,
                    'unit_price' => $unitPriceDollars,
                    'commission_amount' => $commissionAmountDollars,
                    'commission_percent' => $quoteItem->commission_percent ?? 0,
                    'total' => $itemTotalDollars,
                    'delivery_days' => $quoteItem->lead_time_days,
                    'notes' => $notes,
                ]);
            }

            // Update totals
            $proformaInvoice->update([
                'subtotal' => $subtotal,
                'total' => $subtotal,
            ]);

            return $proformaInvoice->fresh();
        });
    }

    /**
     * Generate a unique invoice number
     */
    protected function generateInvoiceNumber(): string
    {
        $prefix = 'PI';
        $year = now()->format('Y');
        $month = now()->format('m');

        // Get the last invoice number for this month
        $lastInvoice = ProformaInvoice::where('invoice_number', 'like', "{$prefix}-{$year}{$month}-%")
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice) {
            // Extract the sequence number and increment
            $lastNumber = (int) substr($lastInvoice->invoice_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('%s-%s%s-%04d', $prefix, $year, $month, $newNumber);
    }
}
