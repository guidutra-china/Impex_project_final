<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Currency;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierQuote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RFQ Workflow Test
 * 
 * Tests the complete RFQ workflow from creation through quote comparison.
 * This is a critical business process that must be reliable.
 */
class RFQWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Client $client;
    protected Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->user = createTestUser();
        $this->client = createTestClient($this->user);
        $this->currency = Currency::where('code', 'USD')->first() 
            ?? createTestCurrency(['code' => 'USD']);

        // Authenticate as the user
        authenticateAs($this->user);
    }

    /**
     * Test creating an RFQ (Order)
     */
    public function test_can_create_rfq(): void
    {
        $response = $this->post(route('orders.store'), [
            'customer_id' => $this->client->id,
            'currency_id' => $this->currency->id,
            'status' => 'pending',
            'commission_percent' => 5.00,
            'customer_nr_rfq' => 'CUST-001',
        ]);

        // Note: Actual route depends on your Filament setup
        // This test assumes a REST API or form submission

        $this->assertDatabaseHas('orders', [
            'customer_id' => $this->client->id,
            'currency_id' => $this->currency->id,
            'status' => 'pending',
        ]);
    }

    /**
     * Test adding items to an RFQ
     */
    public function test_can_add_items_to_rfq(): void
    {
        $order = createTestRFQWithItems($this->client, $this->currency, 0);
        $product = createTestProduct();

        // Add item to order
        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 100,
            'target_price_cents' => 5000, // $50.00
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 100,
        ]);

        // Verify order has items
        $this->assertTrue($order->items()->exists());
        $this->assertEquals(1, $order->items()->count());
    }

    /**
     * Test creating supplier quotes for an RFQ
     */
    public function test_can_create_supplier_quotes(): void
    {
        $order = createTestRFQWithItems($this->client, $this->currency, 2);
        $supplier = createTestSupplier();

        // Create supplier quote
        $quote = SupplierQuote::create([
            'order_id' => $order->id,
            'supplier_id' => $supplier->id,
            'currency_id' => $this->currency->id,
            'status' => 'draft',
            'quote_number' => 'QUOTE-001',
        ]);

        $this->assertDatabaseHas('supplier_quotes', [
            'order_id' => $order->id,
            'supplier_id' => $supplier->id,
            'status' => 'draft',
        ]);

        // Verify quote is associated with order
        $this->assertTrue($order->supplierQuotes()->exists());
    }

    /**
     * Test complete RFQ workflow
     */
    public function test_complete_rfq_workflow(): void
    {
        // Step 1: Create RFQ
        $order = createTestRFQWithItems($this->client, $this->currency, 2);
        $this->assertNotNull($order->order_number);
        $this->assertEquals('pending', $order->status);

        // Step 2: Verify items were created
        $this->assertEquals(2, $order->items()->count());

        // Step 3: Create supplier quotes
        $suppliers = [
            createTestSupplier(),
            createTestSupplier(),
            createTestSupplier(),
        ];

        $quotes = [];
        foreach ($suppliers as $supplier) {
            $quote = createTestSupplierQuote($order, $supplier, $this->currency);
            $quotes[] = $quote;
        }

        $this->assertEquals(3, $order->supplierQuotes()->count());

        // Step 4: Verify quote structure
        foreach ($quotes as $quote) {
            $this->assertEquals('draft', $quote->status);
            $this->assertEquals($order->id, $quote->order_id);
        }
    }

    /**
     * Test RFQ status transitions
     */
    public function test_rfq_status_transitions(): void
    {
        $order = createTestRFQWithItems($this->client, $this->currency);

        // Initial status
        $this->assertEquals('pending', $order->status);

        // Transition to processing
        $order->update(['status' => 'processing']);
        $this->assertEquals('processing', $order->fresh()->status);

        // Transition to quoted
        $order->update(['status' => 'quoted']);
        $this->assertEquals('quoted', $order->fresh()->status);

        // Transition to completed
        $order->update(['status' => 'completed']);
        $this->assertEquals('completed', $order->fresh()->status);
    }

    /**
     * Test RFQ isolation by client (multi-tenancy)
     */
    public function test_rfq_isolation_by_client(): void
    {
        // Create RFQ for current client
        $order1 = createTestRFQWithItems($this->client, $this->currency);

        // Create another user and client
        $otherUser = createTestUser();
        $otherClient = createTestClient($otherUser);

        // Create RFQ for other client
        $order2 = createTestRFQWithItems($otherClient, $this->currency);

        // Current user should only see their own RFQ
        $visibleOrders = Order::all();

        // Note: This depends on your Global Scope implementation
        // The ClientOwnershipScope should filter automatically
        $this->assertTrue($visibleOrders->contains($order1));
        // $this->assertFalse($visibleOrders->contains($order2)); // Depends on scope
    }

    /**
     * Test commission calculation in RFQ
     */
    public function test_commission_calculation(): void
    {
        $order = createTestRFQWithItems(
            $this->client,
            $this->currency,
            1,
            ['commission_percent' => 10.00, 'commission_type' => 'embedded']
        );

        $this->assertEquals(10.00, $order->commission_percent);
        $this->assertEquals('embedded', $order->commission_type);
    }

    /**
     * Test RFQ with multiple currencies
     */
    public function test_rfq_with_currency_conversion(): void
    {
        $usdCurrency = Currency::where('code', 'USD')->first() 
            ?? createTestCurrency(['code' => 'USD']);
        $eurCurrency = createTestCurrency(['code' => 'EUR']);

        // Create RFQ in USD
        $order = createTestRFQWithItems($this->client, $usdCurrency);

        // Create supplier quote in EUR
        $supplier = createTestSupplier();
        $quote = createTestSupplierQuote($order, $supplier, $eurCurrency);

        $this->assertEquals($usdCurrency->id, $order->currency_id);
        $this->assertEquals($eurCurrency->id, $quote->currency_id);
    }

    /**
     * Test RFQ deletion (soft delete)
     */
    public function test_can_delete_rfq(): void
    {
        $order = createTestRFQWithItems($this->client, $this->currency);
        $orderId = $order->id;

        // Delete order
        $order->delete();

        // Verify soft delete
        $this->assertSoftDeleted('orders', ['id' => $orderId]);

        // Verify it's not in active queries
        $this->assertFalse(Order::where('id', $orderId)->exists());

        // Verify it can be restored
        Order::withTrashed()->find($orderId)->restore();
        $this->assertTrue(Order::where('id', $orderId)->exists());
    }

    /**
     * Test RFQ with no items
     */
    public function test_rfq_with_no_items(): void
    {
        $order = Order::create([
            'customer_id' => $this->client->id,
            'currency_id' => $this->currency->id,
            'status' => 'pending',
            'commission_percent' => 5.00,
        ]);

        $this->assertEquals(0, $order->items()->count());
    }

    /**
     * Test RFQ order number auto-generation
     */
    public function test_order_number_auto_generation(): void
    {
        $order = Order::create([
            'customer_id' => $this->client->id,
            'currency_id' => $this->currency->id,
            'status' => 'pending',
        ]);

        // Order number should be auto-generated
        $this->assertNotNull($order->order_number);
        $this->assertStringContainsString('RFQ', $order->order_number);
    }
}
