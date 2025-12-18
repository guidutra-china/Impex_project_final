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
     * Create a Proforma Invoice from Customer Quote product selections
     *
     * @param CustomerQuote $customerQuote
     * @param array $selectedQuoteItemIds
     * @return ProformaInvoice
     */
    public function createFromCustomerQuoteSelection(CustomerQuote $customerQuote, array $selectedQuoteItemIds): ProformaInvoice
    {
        return DB::transaction(function () use ($customerQuote, $selectedQuoteItemIds) {
            // Get the order
            $order = $customerQuote->order;

            // Create Proforma Invoice
            $proformaInvoice = ProformaInvoice::create([
                'order_id' => $order->id,
                'customer_quote_id' => $customerQuote->id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'public_token' => \Str::random(32),
                'status' => 'draft',
                'issue_date' => now(),
                'due_date' => now()->addDays(30),
                'subtotal' => 0,
                'tax_amount' => 0,
                'shipping_cost' => 0,
                'total_amount' => 0,
                'currency_id' => $order->currency_id,
                'notes' => 'Generated from Customer Quote: ' . $customerQuote->quote_number,
                'created_by' => auth()->id(),
            ]);

            // Get selected quote items
            $quoteItems = QuoteItem::whereIn('id', $selectedQuoteItemIds)
                ->with(['product', 'supplierQuote'])
                ->get();

            $subtotal = 0;

            // Create Proforma Invoice Items
            foreach ($quoteItems as $quoteItem) {
                $itemTotal = $quoteItem->unit_price_after_commission * $quoteItem->quantity;
                $subtotal += $itemTotal;

                ProformaInvoiceItem::create([
                    'proforma_invoice_id' => $proformaInvoice->id,
                    'product_id' => $quoteItem->product_id,
                    'description' => $quoteItem->product->name ?? 'Product',
                    'quantity' => $quoteItem->quantity,
                    'unit_price' => $quoteItem->unit_price_after_commission,
                    'total_price' => $itemTotal,
                    'notes' => 'From Supplier: ' . ($quoteItem->supplierQuote->supplier->name ?? 'N/A'),
                ]);
            }

            // Update totals
            $proformaInvoice->update([
                'subtotal' => $subtotal,
                'total_amount' => $subtotal, // Will be updated if tax/shipping is added
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
