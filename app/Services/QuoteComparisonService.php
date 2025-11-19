<?php

namespace App\Services;

use App\Models\Order;
use App\Models\SupplierQuote;

class QuoteComparisonService
{
    /**
     * Compare all supplier quotes for an order
     *
     * @param Order $order
     * @return array
     */
    public function compareQuotes(Order $order): array
    {
        $quotes = $order->supplierQuotes()
//            ->where('status', '!=', 'draft')
            ->with(['items.product', 'supplier', 'currency'])
            ->get();

        if ($quotes->isEmpty()) {
            return [
                'by_product' => [],
                'overall' => null,
                'message' => 'No quotes available for comparison',
            ];
        }

        $comparison = [
            'by_product' => $this->compareByProduct($order, $quotes),
            'overall' => $this->compareOverall($quotes, $order),
        ];

        return $comparison;
    }

    /**
     * Compare quotes by individual product
     *
     * @param Order $order
     * @param \Illuminate\Support\Collection $quotes
     * @return array
     */
    protected function compareByProduct(Order $order, $quotes): array
    {
        $comparison = [];

        foreach ($order->items as $orderItem) {
            $productId = $orderItem->product_id;
            $cheapest = null;
            $allPrices = [];

            foreach ($quotes as $quote) {
                $quoteItem = $quote->items()
                    ->where('product_id', $productId)
                    ->first();

                if (!$quoteItem) {
                    $allPrices[] = [
                        'supplier_id' => $quote->supplier->id,
                        'supplier' => $quote->supplier->name,
                        'quote_id' => $quote->id,
                        'price' => null,
                        'converted_price' => null,
                        'currency' => $quote->currency->symbol,
                        'status' => 'Not Quoted',
                    ];
                    continue;
                }

                // Use converted price for comparison (in order currency)
                $convertedPrice = $quoteItem->converted_price_cents ?? $quoteItem->total_price_after_commission;
                
                $allPrices[] = [
                    'supplier_id' => $quote->supplier->id,
                    'supplier' => $quote->supplier->name,
                    'quote_id' => $quote->id,
                    'price' => $quoteItem->unit_price_after_commission,
                    'total' => $quoteItem->total_price_after_commission,
                    'converted_price' => $convertedPrice,
                    'currency' => $quote->currency->symbol,
                    'order_currency' => $order->currency->symbol,
                    'exchange_rate' => $quote->locked_exchange_rate,
                    'status' => 'Quoted',
                ];

                if (!$cheapest || $convertedPrice < $cheapest['converted_price']) {
                    $cheapest = [
                        'supplier_id' => $quote->supplier->id,
                        'supplier' => $quote->supplier->name,
                        'quote_id' => $quote->id,
                        'price' => $quoteItem->unit_price_after_commission,
                        'converted_price' => $convertedPrice,
                        'currency' => $quote->currency->symbol,
                        'order_currency' => $order->currency->symbol,
                        'exchange_rate' => $quote->locked_exchange_rate,
                    ];
                }
            }

            // Calculate savings
            if ($cheapest && count($allPrices) > 1) {
                $maxPrice = collect($allPrices)
                    ->where('status', 'Quoted')
                    ->max('converted_price');
                
                $savings = $maxPrice - $cheapest['converted_price'];
                $savingsPercent = $maxPrice > 0 ? ($savings / $maxPrice) * 100 : 0;
            } else {
                $savings = 0;
                $savingsPercent = 0;
            }

            $comparison[] = [
                'product_id' => $orderItem->product->id,
                'product' => $orderItem->product->name,
                'product_code' => $orderItem->product->code,
                'quantity' => $orderItem->quantity,
                'cheapest' => $cheapest,
                'all_prices' => $allPrices,
                'savings' => $savings,
                'savings_percent' => round($savingsPercent, 2),
            ];
        }

        return $comparison;
    }

    /**
     * Compare quotes overall (total price)
     *
     * @param \Illuminate\Support\Collection $quotes
     * @param Order $order
     * @return array|null
     */
    protected function compareOverall($quotes, Order $order): ?array
    {
        if ($quotes->isEmpty()) {
            return null;
        }

        // Sort by sum of converted item prices for fair comparison
        $cheapestOverall = $quotes->sortBy(function ($quote) {
            return $quote->items->sum(function ($item) use ($quote) {
                return $item->converted_price_cents ?? (
                    $quote->locked_exchange_rate 
                        ? (int) round($item->total_price_after_commission / $quote->locked_exchange_rate)
                        : $item->total_price_after_commission
                );
            });
        })->first();
        
        $mostExpensive = $quotes->sortByDesc(function ($quote) {
            return $quote->items->sum(function ($item) use ($quote) {
                return $item->converted_price_cents ?? (
                    $quote->locked_exchange_rate 
                        ? (int) round($item->total_price_after_commission / $quote->locked_exchange_rate)
                        : $item->total_price_after_commission
                );
            });
        })->first();

        $cheapestConverted = $cheapestOverall->items->sum(function ($item) use ($cheapestOverall) {
            return $item->converted_price_cents ?? (
                $cheapestOverall->locked_exchange_rate 
                    ? (int) round($item->total_price_after_commission / $cheapestOverall->locked_exchange_rate)
                    : $item->total_price_after_commission
            );
        });
        
        $mostExpensiveConverted = $mostExpensive->items->sum(function ($item) use ($mostExpensive) {
            return $item->converted_price_cents ?? (
                $mostExpensive->locked_exchange_rate 
                    ? (int) round($item->total_price_after_commission / $mostExpensive->locked_exchange_rate)
                    : $item->total_price_after_commission
            );
        });

        $savings = $mostExpensiveConverted - $cheapestConverted;
        $savingsPercent = $mostExpensiveConverted > 0 
            ? ($savings / $mostExpensiveConverted) * 100 
            : 0;

        $allQuotes = $quotes->map(function ($quote) use ($order) {
            // Sum converted item prices instead of converting total
            $convertedTotal = $quote->items->sum(function ($item) use ($quote) {
                return $item->converted_price_cents ?? (
                    $quote->locked_exchange_rate 
                        ? (int) round($item->total_price_after_commission / $quote->locked_exchange_rate)
                        : $item->total_price_after_commission
                );
            });
            
            return [
                'quote_id' => $quote->id,
                'supplier_id' => $quote->supplier->id,
                'supplier' => $quote->supplier->name,
                'total_before_commission' => $quote->total_price_before_commission,
                'total_after_commission' => $convertedTotal,  // Sum of converted item prices
                'original_total' => $quote->total_price_after_commission,  // Keep original for reference
                'commission_amount' => $quote->commission_amount,
                'currency' => $quote->currency->symbol,
                'order_currency' => $order->currency->symbol,
                'exchange_rate' => $quote->locked_exchange_rate,
                'status' => $quote->status,
            ];
        })->toArray();

        return [
            'cheapest_quote_id' => $cheapestOverall->id,
            'cheapest_supplier_id' => $cheapestOverall->supplier->id,
            'cheapest_supplier' => $cheapestOverall->supplier->name,
            'cheapest_total' => $cheapestOverall->total_price_after_commission,
            'cheapest_currency' => $cheapestOverall->currency->symbol,
            'most_expensive_total' => $mostExpensive->total_price_after_commission,
            'savings' => $savings,
            'savings_percent' => round($savingsPercent, 2),
            'order_currency' => $order->currency->symbol,
            'all_quotes' => $allQuotes,
        ];
    }

    /**
     * Get summary statistics for an order
     *
     * @param Order $order
     * @return array
     */
    public function getSummary(Order $order): array
    {
        $quotes = $order->supplierQuotes;

        return [
            'total_quotes' => $quotes->count(),
            'draft_quotes' => $quotes->where('status', 'draft')->count(),
            'sent_quotes' => $quotes->where('status', 'sent')->count(),
            'accepted_quotes' => $quotes->where('status', 'accepted')->count(),
            'rejected_quotes' => $quotes->where('status', 'rejected')->count(),
            'total_products' => $order->items->count(),
            'order_currency' => $order->currency->code ?? 'USD',
        ];
    }
}
