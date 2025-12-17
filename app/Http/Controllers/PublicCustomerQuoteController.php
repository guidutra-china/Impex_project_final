<?php

namespace App\Http\Controllers;

use App\Models\CustomerQuote;
use App\Services\CustomerQuoteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicCustomerQuoteController extends Controller
{
    public function __construct(
        private CustomerQuoteService $quoteService
    ) {}

    /**
     * Show public customer quote page (no login required)
     */
    public function show(string $token)
    {
        // Find quote by public token
        $quote = CustomerQuote::where('public_token', $token)
            ->with([
                'order.customer',
                'items.supplierQuoteItem.product',
                'items.supplierQuote.supplier',
            ])
            ->firstOrFail();

        // Track view if not already viewed
        if (!$quote->viewed_at) {
            $quote->update([
                'viewed_at' => now(),
                'status' => 'viewed',
            ]);
        }

        return view('public.customer-quote', [
            'quote' => $quote,
            'customer' => $quote->order->customer,
            'options' => $quote->items->sortBy('display_order'),
        ]);
    }

    /**
     * Handle option selection from public page
     */
    public function selectOption(Request $request, string $token)
    {
        $request->validate([
            'item_id' => 'required|exists:customer_quote_items,id',
        ]);

        $quote = CustomerQuote::where('public_token', $token)->firstOrFail();

        // Verify the item belongs to this quote
        $item = $quote->items()->findOrFail($request->item_id);

        try {
            DB::beginTransaction();

            // Mark item as selected
            $this->quoteService->approveItem($quote, $item->id);

            DB::commit();

            return redirect()
                ->route('public.customer-quote.show', $token)
                ->with('success', 'Thank you! Your selection has been recorded. Our team will contact you shortly.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('public.customer-quote.show', $token)
                ->with('error', 'An error occurred. Please try again or contact us directly.');
        }
    }
}
