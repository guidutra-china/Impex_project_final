<?php

namespace App\Observers;

use App\Models\QuoteItem;
use Illuminate\Support\Facades\Log;

class QuoteItemObserver
{
    /**
     * Handle the QuoteItem "created" event.
     */
    public function created(QuoteItem $quoteItem): void
    {
        $this->recalculateSupplierQuote($quoteItem);
    }

    /**
     * Handle the QuoteItem "updated" event.
     */
    public function updated(QuoteItem $quoteItem): void
    {
        $this->recalculateSupplierQuote($quoteItem);
    }

    /**
     * Handle the QuoteItem "deleted" event.
     */
    public function deleted(QuoteItem $quoteItem): void
    {
        $this->recalculateSupplierQuote($quoteItem);
    }

    /**
     * Recalculate supplier quote totals
     */
    protected function recalculateSupplierQuote(QuoteItem $quoteItem): void
    {
        try {
            $supplierQuote = $quoteItem->supplierQuote;
            
            if (!$supplierQuote) {
                Log::warning('QuoteItemObserver: No supplier quote found for item', [
                    'quote_item_id' => $quoteItem->id,
                ]);
                return;
            }

            // Reload items to get fresh data
            $supplierQuote->load('items');
            
            // Recalculate commission and totals
            $supplierQuote->calculateCommission();
            
            Log::info('QuoteItemObserver: Recalculated supplier quote totals', [
                'supplier_quote_id' => $supplierQuote->id,
                'quote_item_id' => $quoteItem->id,
                'total_before' => $supplierQuote->total_price_before_commission,
                'total_after' => $supplierQuote->total_price_after_commission,
            ]);
        } catch (\Exception $e) {
            Log::error('QuoteItemObserver: Failed to recalculate supplier quote', [
                'quote_item_id' => $quoteItem->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
