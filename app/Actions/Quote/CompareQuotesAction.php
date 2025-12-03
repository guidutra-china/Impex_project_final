<?php

namespace App\Actions\Quote;

use App\Models\Order;
use App\Services\QuoteComparisonService;
use Illuminate\Support\Facades\Log;

/**
 * CompareQuotesAction
 * 
 * Handles the comparison of supplier quotes for an order.
 * This action encapsulates the logic for analyzing and comparing multiple quotes
 * to help identify the best supplier option.
 * 
 * @example
 * $action = new CompareQuotesAction(new QuoteComparisonService());
 * $comparison = $action->execute($order);
 */
class CompareQuotesAction
{
    /**
     * Create a new action instance
     */
    public function __construct(
        private QuoteComparisonService $comparisonService
    ) {
    }

    /**
     * Execute the quote comparison action
     * 
     * @param Order $order The order to compare quotes for
     * @return array Comparison results with detailed analysis
     */
    public function execute(Order $order): array
    {
        return $this->comparisonService->compareQuotes($order);
    }

    /**
     * Handle the quote comparison with validation and logging
     * 
     * @param Order $order
     * @param array $options Additional options for the comparison
     * @return array
     */
    public function handle(Order $order, array $options = []): array
    {
        // Validate that the order exists
        if (!$order->exists) {
            throw new \Exception('Order does not exist');
        }

        // Check if the order has any quotes
        $quoteCount = $order->supplierQuotes()->count();
        if ($quoteCount === 0) {
            Log::warning('No quotes found for order', [
                'order_id' => $order->id,
            ]);
            return [];
        }

        // Log the comparison attempt
        Log::info('Starting quote comparison', [
            'order_id' => $order->id,
            'quote_count' => $quoteCount,
            'options' => $options,
        ]);

        try {
            $result = $this->execute($order);

            Log::info('Quote comparison completed', [
                'order_id' => $order->id,
                'by_product_count' => count($result['by_product'] ?? []),
                'has_overall' => isset($result['overall']),
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('Quote comparison failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get the cheapest quote for an order
     * 
     * @param Order $order
     * @return array|null The cheapest quote details or null if no quotes exist
     */
    public function getCheapestQuote(Order $order): ?array
    {
        $comparison = $this->execute($order);

        if (empty($comparison['overall'])) {
            return null;
        }

        return $comparison['overall'];
    }

    /**
     * Get quotes ranked by price
     * 
     * @param Order $order
     * @return array Quotes ranked from cheapest to most expensive
     */
    public function getRankedQuotes(Order $order): array
    {
        $comparison = $this->execute($order);

        if (empty($comparison['by_product'])) {
            return [];
        }

        // Extract and rank quotes by total price
        $quotes = [];
        foreach ($comparison['by_product'] as $productComparison) {
            if (isset($productComparison['quotes'])) {
                foreach ($productComparison['quotes'] as $quote) {
                    $quotes[] = $quote;
                }
            }
        }

        // Sort by price
        usort($quotes, function ($a, $b) {
            $priceA = $a['total_price_after_commission'] ?? $a['total_price_before_commission'] ?? 0;
            $priceB = $b['total_price_after_commission'] ?? $b['total_price_before_commission'] ?? 0;
            return $priceA <=> $priceB;
        });

        return $quotes;
    }
}
