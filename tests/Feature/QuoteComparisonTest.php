<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Currency;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\QuoteItem;
use App\Models\Supplier;
use App\Models\SupplierQuote;
use App\Models\User;
use App\Services\QuoteComparisonService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Quote Comparison Test
 * 
 * Tests the quote comparison functionality which is critical for
 * selecting the best supplier offer.
 */
class QuoteComparisonTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Client $client;
    protected Currency $currency;
    protected QuoteComparisonService $comparisonService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = createTestUser();
        $this->client = createTestClient($this->user);
        $this->currency = Currency::where('code', 'USD')->first() 
            ?? createTestCurrency(['code' => 'USD']);

        $this->comparisonService = new QuoteComparisonService();
        authenticateAs($this->user);
    }

    /**
     * Test comparing quotes for a single product
     */
    public function test_can_compare_quotes_for_single_product(): void
    {
        // Create RFQ with one item
        $order = createTestRFQWithItems($this->client, $this->currency, 1);
        $product = $order->items()->first()->product;

        // Create multiple supplier quotes
        $suppliers = [
            createTestSupplier(['name' => 'Supplier A']),
            createTestSupplier(['name' => 'Supplier B']),
            createTestSupplier(['name' => 'Supplier C']),
        ];

        $quotes = [];
        $prices = [100, 95, 110]; // Different prices

        foreach ($suppliers as $index => $supplier) {
            $quote = createTestSupplierQuote($order, $supplier, $this->currency);
            
            // Add quote item with price
            QuoteItem::create([
                'supplier_quote_id' => $quote->id,
                'order_item_id' => $order->items()->first()->id,
                'product_id' => $product->id,
                'quantity' => 100,
                'unit_price_before_commission' => $prices[$index],
                'unit_price_after_commission' => $prices[$index],
                'total_price_before_commission' => $prices[$index] * 100,
                'total_price_after_commission' => $prices[$index] * 100,
                'converted_price_cents' => ($prices[$index] * 100) * 100, // in cents
            ]);

            $quotes[] = $quote;
        }

        // Compare quotes
        $comparison = $this->comparisonService->compareQuotes($order);

        $this->assertNotEmpty($comparison['by_product']);
        $this->assertNotNull($comparison['overall']);
    }

    /**
     * Test identifying cheapest quote
     */
    public function test_identifies_cheapest_quote(): void
    {
        $order = createTestRFQWithItems($this->client, $this->currency, 1);
        $product = $order->items()->first()->product;

        // Create suppliers with different prices
        $cheapSupplier = createTestSupplier(['name' => 'Cheap Supplier']);
        $expensiveSupplier = createTestSupplier(['name' => 'Expensive Supplier']);

        // Cheap quote
        $cheapQuote = createTestSupplierQuote($order, $cheapSupplier, $this->currency);
        QuoteItem::create([
            'supplier_quote_id' => $cheapQuote->id,
            'order_item_id' => $order->items()->first()->id,
            'product_id' => $product->id,
            'quantity' => 100,
            'unit_price_before_commission' => 50,
            'unit_price_after_commission' => 50,
            'total_price_before_commission' => 5000,
            'total_price_after_commission' => 5000,
            'converted_price_cents' => 500000,
        ]);

        // Expensive quote
        $expensiveQuote = createTestSupplierQuote($order, $expensiveSupplier, $this->currency);
        QuoteItem::create([
            'supplier_quote_id' => $expensiveQuote->id,
            'order_item_id' => $order->items()->first()->id,
            'product_id' => $product->id,
            'quantity' => 100,
            'unit_price_before_commission' => 100,
            'unit_price_after_commission' => 100,
            'total_price_before_commission' => 10000,
            'total_price_after_commission' => 10000,
            'converted_price_cents' => 1000000,
        ]);

        $comparison = $this->comparisonService->compareQuotes($order);

        // Verify cheapest is identified
        $this->assertNotEmpty($comparison['by_product']);
    }

    /**
     * Test comparing quotes with multiple products
     */
    public function test_comparing_quotes_with_multiple_products(): void
    {
        // Create RFQ with multiple items
        $order = createTestRFQWithItems($this->client, $this->currency, 3);

        // Create supplier quotes
        $suppliers = [
            createTestSupplier(),
            createTestSupplier(),
        ];

        foreach ($suppliers as $supplier) {
            $quote = createTestSupplierQuote($order, $supplier, $this->currency);

            // Add quote items for each product
            foreach ($order->items() as $item) {
                QuoteItem::create([
                    'supplier_quote_id' => $quote->id,
                    'order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price_before_commission' => rand(50, 200),
                    'unit_price_after_commission' => rand(50, 200),
                    'total_price_before_commission' => rand(5000, 20000),
                    'total_price_after_commission' => rand(5000, 20000),
                    'converted_price_cents' => rand(500000, 2000000),
                ]);
            }
        }

        $comparison = $this->comparisonService->compareQuotes($order);

        // Should have comparison for each product
        $this->assertEquals(3, count($comparison['by_product']));
    }

    /**
     * Test comparing quotes with missing items
     */
    public function test_comparing_quotes_with_missing_items(): void
    {
        $order = createTestRFQWithItems($this->client, $this->currency, 2);

        // Create supplier quote with only one item
        $supplier = createTestSupplier();
        $quote = createTestSupplierQuote($order, $supplier, $this->currency);

        // Add quote item for only the first product
        $firstItem = $order->items()->first();
        QuoteItem::create([
            'supplier_quote_id' => $quote->id,
            'order_item_id' => $firstItem->id,
            'product_id' => $firstItem->product_id,
            'quantity' => $firstItem->quantity,
            'unit_price_before_commission' => 100,
            'unit_price_after_commission' => 100,
            'total_price_before_commission' => 10000,
            'total_price_after_commission' => 10000,
            'converted_price_cents' => 1000000,
        ]);

        $comparison = $this->comparisonService->compareQuotes($order);

        // Should handle missing items gracefully
        $this->assertNotEmpty($comparison['by_product']);
    }

    /**
     * Test comparing quotes with no quotes
     */
    public function test_comparing_quotes_with_no_quotes(): void
    {
        $order = createTestRFQWithItems($this->client, $this->currency, 1);

        $comparison = $this->comparisonService->compareQuotes($order);

        $this->assertEmpty($comparison['by_product']);
        $this->assertNull($comparison['overall']);
        $this->assertStringContainsString('No quotes', $comparison['message']);
    }

    /**
     * Test overall quote comparison
     */
    public function test_overall_quote_comparison(): void
    {
        $order = createTestRFQWithItems($this->client, $this->currency, 2);

        // Create multiple quotes
        $suppliers = [
            createTestSupplier(['name' => 'Supplier A']),
            createTestSupplier(['name' => 'Supplier B']),
        ];

        foreach ($suppliers as $supplier) {
            $quote = createTestSupplierQuote($order, $supplier, $this->currency);

            foreach ($order->items() as $item) {
                QuoteItem::create([
                    'supplier_quote_id' => $quote->id,
                    'order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price_before_commission' => 100,
                    'unit_price_after_commission' => 100,
                    'total_price_before_commission' => 10000,
                    'total_price_after_commission' => 10000,
                    'converted_price_cents' => 1000000,
                ]);
            }
        }

        $comparison = $this->comparisonService->compareQuotes($order);

        $this->assertNotNull($comparison['overall']);
    }

    /**
     * Test selecting a quote as winner
     */
    public function test_can_select_quote_as_winner(): void
    {
        $order = createTestRFQWithItems($this->client, $this->currency, 1);
        $supplier = createTestSupplier();
        $quote = createTestSupplierQuote($order, $supplier, $this->currency);

        // Select quote
        $order->update(['selected_quote_id' => $quote->id]);

        $this->assertEquals($quote->id, $order->fresh()->selected_quote_id);
        $this->assertEquals($quote->id, $order->selectedQuote()->first()->id);
    }

    /**
     * Test quote comparison with currency conversion
     */
    public function test_quote_comparison_with_currency_conversion(): void
    {
        $order = createTestRFQWithItems($this->client, $this->currency, 1);

        // Create quotes in different currencies
        $usdCurrency = $this->currency;
        $eurCurrency = createTestCurrency(['code' => 'EUR']);

        $usdSupplier = createTestSupplier();
        $eurSupplier = createTestSupplier();

        // USD quote
        $usdQuote = createTestSupplierQuote($order, $usdSupplier, $usdCurrency);
        QuoteItem::create([
            'supplier_quote_id' => $usdQuote->id,
            'order_item_id' => $order->items()->first()->id,
            'product_id' => $order->items()->first()->product_id,
            'quantity' => 100,
            'unit_price_before_commission' => 100,
            'unit_price_after_commission' => 100,
            'total_price_before_commission' => 10000,
            'total_price_after_commission' => 10000,
            'converted_price_cents' => 1000000,
        ]);

        // EUR quote
        $eurQuote = createTestSupplierQuote($order, $eurSupplier, $eurCurrency);
        QuoteItem::create([
            'supplier_quote_id' => $eurQuote->id,
            'order_item_id' => $order->items()->first()->id,
            'product_id' => $order->items()->first()->product_id,
            'quantity' => 100,
            'unit_price_before_commission' => 90, // EUR
            'unit_price_after_commission' => 90,
            'total_price_before_commission' => 9000,
            'total_price_after_commission' => 9000,
            'converted_price_cents' => 900000, // Converted to order currency
        ]);

        $comparison = $this->comparisonService->compareQuotes($order);

        $this->assertNotEmpty($comparison['by_product']);
    }
}
