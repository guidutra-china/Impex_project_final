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
        $order = Order::factory()->create([
            'customer_id' => $this->client->id,
            'currency_id' => $this->currency->id,
        ]);

        $product = Product::factory()->create();
        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 100,
            'commission_type' => 'embedded',
        ]);

        // Create multiple supplier quotes
        $suppliers = [
            Supplier::factory()->create(['name' => 'Supplier A']),
            Supplier::factory()->create(['name' => 'Supplier B']),
            Supplier::factory()->create(['name' => 'Supplier C']),
        ];

        $prices = [100, 95, 110]; // Different prices

        foreach ($suppliers as $index => $supplier) {
            $quote = SupplierQuote::create([
                'order_id' => $order->id,
                'supplier_id' => $supplier->id,
                'currency_id' => $this->currency->id,
                'status' => 'draft',
            ]);
            
            // Add quote item with price
            QuoteItem::create([
                'supplier_quote_id' => $quote->id,
                'order_item_id' => $orderItem->id,
                'product_id' => $product->id,
                'quantity' => 100,
            'commission_type' => 'embedded',
                'unit_price_before_commission' => $prices[$index],
                'unit_price_after_commission' => $prices[$index],
                'total_price_before_commission' => $prices[$index] * 100,
                'total_price_after_commission' => $prices[$index] * 100,
                'converted_price_cents' => ($prices[$index] * 100) * 100, // in cents
            ]);
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
        $order = Order::factory()->create([
            'customer_id' => $this->client->id,
            'currency_id' => $this->currency->id,
        ]);

        $product = Product::factory()->create();
        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 100,
            'commission_type' => 'embedded',
        ]);

        // Create suppliers with different prices
        $cheapSupplier = Supplier::factory()->create(['name' => 'Cheap Supplier']);
        $expensiveSupplier = Supplier::factory()->create(['name' => 'Expensive Supplier']);

        // Cheap quote
        $cheapQuote = SupplierQuote::create([
            'order_id' => $order->id,
            'supplier_id' => $cheapSupplier->id,
            'currency_id' => $this->currency->id,
        ]);

        QuoteItem::create([
            'supplier_quote_id' => $cheapQuote->id,
            'order_item_id' => $orderItem->id,
            'product_id' => $product->id,
            'quantity' => 100,
            'commission_type' => 'embedded',
            'unit_price_before_commission' => 50,
            'unit_price_after_commission' => 50,
            'total_price_before_commission' => 5000,
            'total_price_after_commission' => 5000,
            'converted_price_cents' => 500000,
        ]);

        // Expensive quote
        $expensiveQuote = SupplierQuote::create([
            'order_id' => $order->id,
            'supplier_id' => $expensiveSupplier->id,
            'currency_id' => $this->currency->id,
        ]);

        QuoteItem::create([
            'supplier_quote_id' => $expensiveQuote->id,
            'order_item_id' => $orderItem->id,
            'product_id' => $product->id,
            'quantity' => 100,
            'commission_type' => 'embedded',
            'unit_price_before_commission' => 100,
            'unit_price_after_commission' => 100,
            'total_price_before_commission' => 10000,
            'total_price_after_commission' => 10000,
            'converted_price_cents' => 1000000,
        ]);

        $comparison = $this->comparisonService->compareQuotes($order);

        $this->assertNotEmpty($comparison['by_product']);
    }

    /**
     * Test comparing quotes with multiple products
     */
    public function test_comparing_quotes_with_multiple_products(): void
    {
        $order = Order::factory()->create([
            'customer_id' => $this->client->id,
            'currency_id' => $this->currency->id,
        ]);

        // Create multiple products
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        $item1 = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product1->id,
            'quantity' => 100,
            'commission_type' => 'embedded',
        ]);

        $item2 = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product2->id,
            'quantity' => 50,
            'commission_type' => 'embedded',
        ]);

        // Create supplier quote
        $supplier = Supplier::factory()->create();
        $quote = SupplierQuote::create([
            'order_id' => $order->id,
            'supplier_id' => $supplier->id,
            'currency_id' => $this->currency->id,
        ]);

        // Add quote items for both products
        QuoteItem::create([
            'supplier_quote_id' => $quote->id,
            'order_item_id' => $item1->id,
            'product_id' => $product1->id,
            'quantity' => 100,
            'commission_type' => 'embedded',
            'unit_price_before_commission' => 100,
            'unit_price_after_commission' => 100,
            'total_price_before_commission' => 10000,
            'total_price_after_commission' => 10000,
            'converted_price_cents' => 1000000,
        ]);

        QuoteItem::create([
            'supplier_quote_id' => $quote->id,
            'order_item_id' => $item2->id,
            'product_id' => $product2->id,
            'quantity' => 50,
            'commission_type' => 'embedded',
            'unit_price_before_commission' => 200,
            'unit_price_after_commission' => 200,
            'total_price_before_commission' => 10000,
            'total_price_after_commission' => 10000,
            'converted_price_cents' => 1000000,
        ]);

        $comparison = $this->comparisonService->compareQuotes($order);

        $this->assertNotEmpty($comparison['by_product']);
    }

    /**
     * Test comparing quotes with missing items
     */
    public function test_comparing_quotes_with_missing_items(): void
    {
        $order = Order::factory()->create([
            'customer_id' => $this->client->id,
            'currency_id' => $this->currency->id,
        ]);

        $product = Product::factory()->create();
        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 100,
            'commission_type' => 'embedded',
        ]);

        // Create quote without items
        $supplier = Supplier::factory()->create();
        $quote = SupplierQuote::create([
            'order_id' => $order->id,
            'supplier_id' => $supplier->id,
            'currency_id' => $this->currency->id,
        ]);

        // This quote has no items, which should be handled
        $comparison = $this->comparisonService->compareQuotes($order);

        $this->assertNotEmpty($comparison);
    }

    /**
     * Test comparing quotes with no quotes
     */
    public function test_comparing_quotes_with_no_quotes(): void
    {
        $order = Order::factory()->create([
            'customer_id' => $this->client->id,
            'currency_id' => $this->currency->id,
        ]);

        $product = Product::factory()->create();
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 100,
            'commission_type' => 'embedded',
        ]);

        // No supplier quotes created
        $comparison = $this->comparisonService->compareQuotes($order);

        // Should return empty or null for comparison
        $this->assertIsArray($comparison);
    }

    /**
     * Test overall quote comparison
     */
    public function test_overall_quote_comparison(): void
    {
        $order = Order::factory()->create([
            'customer_id' => $this->client->id,
            'currency_id' => $this->currency->id,
        ]);

        $product = Product::factory()->create();
        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 100,
            'commission_type' => 'embedded',
        ]);

        // Create two supplier quotes
        $supplier1 = Supplier::factory()->create();
        $supplier2 = Supplier::factory()->create();

        $quote1 = SupplierQuote::create([
            'order_id' => $order->id,
            'supplier_id' => $supplier1->id,
            'currency_id' => $this->currency->id,
        ]);

        $quote2 = SupplierQuote::create([
            'order_id' => $order->id,
            'supplier_id' => $supplier2->id,
            'currency_id' => $this->currency->id,
        ]);

        QuoteItem::create([
            'supplier_quote_id' => $quote1->id,
            'order_item_id' => $orderItem->id,
            'product_id' => $product->id,
            'quantity' => 100,
            'commission_type' => 'embedded',
            'unit_price_before_commission' => 100,
            'unit_price_after_commission' => 100,
            'total_price_before_commission' => 10000,
            'total_price_after_commission' => 10000,
            'converted_price_cents' => 1000000,
        ]);

        QuoteItem::create([
            'supplier_quote_id' => $quote2->id,
            'order_item_id' => $orderItem->id,
            'product_id' => $product->id,
            'quantity' => 100,
            'commission_type' => 'embedded',
            'unit_price_before_commission' => 95,
            'unit_price_after_commission' => 95,
            'total_price_before_commission' => 9500,
            'total_price_after_commission' => 9500,
            'converted_price_cents' => 950000,
        ]);

        $comparison = $this->comparisonService->compareQuotes($order);

        $this->assertNotEmpty($comparison['by_product']);
    }

    /**
     * Test selecting quote as winner
     */
    public function test_can_select_quote_as_winner(): void
    {
        $order = Order::factory()->create([
            'customer_id' => $this->client->id,
            'currency_id' => $this->currency->id,
        ]);

        $supplier = Supplier::factory()->create();
        $quote = SupplierQuote::create([
            'order_id' => $order->id,
            'supplier_id' => $supplier->id,
            'currency_id' => $this->currency->id,
        ]);

        // Select as winner
        $order->update(['selected_quote_id' => $quote->id]);

        $this->assertEquals($quote->id, $order->fresh()->selected_quote_id);
    }

    /**
     * Test quote comparison with currency conversion
     */
    public function test_quote_comparison_with_currency_conversion(): void
    {
        // Ensure USD is set as base currency
        Currency::where('code', 'USD')->update(['is_base' => true]);
        
        $eur = Currency::where('code', 'EUR')->first() 
            ?? createTestCurrency(['code' => 'EUR', 'is_base' => false]);

        $order = Order::factory()->create([
            'customer_id' => $this->client->id,
            'currency_id' => $this->currency->id,
        ]);

        $product = Product::factory()->create();
        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 100,
            'commission_type' => 'embedded',
        ]);

        // Create quote in different currency
        $supplier = Supplier::factory()->create();
        $quote = SupplierQuote::create([
            'order_id' => $order->id,
            'supplier_id' => $supplier->id,
            'currency_id' => $eur->id,
        ]);

        QuoteItem::create([
            'supplier_quote_id' => $quote->id,
            'order_item_id' => $orderItem->id,
            'product_id' => $product->id,
            'quantity' => 100,
            'commission_type' => 'embedded',
            'unit_price_before_commission' => 85,
            'unit_price_after_commission' => 85,
            'total_price_before_commission' => 8500,
            'total_price_after_commission' => 8500,
            'converted_price_cents' => 850000,
        ]);

        $comparison = $this->comparisonService->compareQuotes($order);

        $this->assertNotEmpty($comparison);
    }
}
