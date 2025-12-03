<?php

namespace App\Actions\Quote;

use App\Models\Order;
use App\Services\QuoteComparisonService;
use Illuminate\Support\Facades\Log;

/**
 * CompareQuotesAction
 * 
 * Business logic action for comparing supplier quotes for an order.
 * This action encapsulates the core business logic for quote comparison,
 * separate from UI concerns. It can be used in multiple contexts:
 * - Filament Resources (via Action::make())
 * - Controllers
 * - Jobs/Queues
 * - API endpoints
 * - Livewire Components
 * 
 * Filament V4 Pattern:
 * Actions in Filament V4 are primarily UI-centric, but this class
 * represents the underlying business logic that can be invoked from
 * Filament Actions or other contexts.
 * 
 * @example
 * // In a Filament Resource or Component:
 * $action = app(CompareQuotesAction::class);
 * $comparison = $action->execute($order);
 * 
 * // Or via Filament Action:
 * Action::make('compare')
 *     ->action(fn (CompareQuotesAction $action, Order $order) =>
 *         $action->execute($order)
 *     )
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
     * Execute the quote comparison
     * 
     * This is the main entry point for the action. It analyzes and compares
     * all supplier quotes for the given order.
     * 
     * @param Order $order The order to compare quotes for
     * @return array Comparison results with detailed analysis
     */
    public function execute(Order $order): array
    {
        try {
            return $this->comparisonService->compareQuotes($order);
        } catch (\Exception $e) {
            Log::error('Quote comparison failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Execute with validation
     * 
     * Use this method when you want to perform validation before comparison.
     * This is useful when called from Filament Actions where you might have
     * additional context.
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

        Log::info('Starting quote comparison', [
            'order_id' => $order->id,
            'quote_count' => $quoteCount,
            'options' => $options,
        ]);

        return $this->execute($order);
    }

    /**
     * Get the cheapest quote for an order
     * 
     * Convenience method to get the best price option.
     * 
     * @param Order $order
     * @return array|null The cheapest quote details or null if no quotes exist
     */
    public function getCheapestQuote(Order $order): ?array
    {
        $comparison = $this->execute($order);
        return $comparison['overall'] ?? null;
    }

    /**
     * Get quotes ranked by price
     * 
     * Convenience method to get all quotes sorted by price.
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
