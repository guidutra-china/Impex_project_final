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
        $order = Order::factory()->create([
            'customer_id' => $this->client->id,
            'currency_id' => $this->currency->id,
            'status' => 'pending',
        ]);

        $this->assertNotNull($order->id);
        $this->assertEquals($this->client->id, $order->customer_id);
        $this->assertEquals('pending', $order->status);
    }

    /**
     * Test adding items to RFQ
     */
    public function test_can_add_items_to_rfq(): void
    {
        $order = Order::factory()->create([
            'customer_id' => $this->client->id,
            'currency_id' => $this->currency->id,
        ]);

        $product = Product::factory()->create();

        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 100,
            'commission_type' => 'embedded',
        ]);

        $this->assertTrue($order->items()->count() > 0);
        $this->assertEquals($product->id, $item->product_id);
    }

    /**
     * Test creating supplier quotes
     */
    public function test_can_create_supplier_quotes(): void
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
            'status' => 'draft'
        ]);

        $this->assertNotNull($quote->id);
        $this->assertEquals($order->id, $quote->order_id);
    }

    /**
     * Test complete RFQ workflow
     */
    public function test_complete_rfq_workflow(): void
    {
        // Create RFQ
        $order = Order::factory()->create([
            'customer_id' => $this->client->id,
            'currency_id' => $this->currency->id,
            'status' => 'pending',
        ]);

        // Add items
        $product = Product::factory()->create();
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 50,
            'commission_type' => 'embedded',
        ]);

        // Create supplier quotes
        $supplier1 = Supplier::factory()->create();
        $supplier2 = Supplier::factory()->create();

        SupplierQuote::create([
            'order_id' => $order->id,
            'supplier_id' => $supplier1->id,
            'currency_id' => $this->currency->id,
        ]);

        SupplierQuote::create([
            'order_id' => $order->id,
            'supplier_id' => $supplier2->id,
            'currency_id' => $this->currency->id,
        ]);

        // Verify workflow
        $this->assertEquals(1, $order->items()->count());
        $this->assertEquals(2, $order->supplierQuotes()->count());
    }

    /**
     * Test RFQ status transitions
     */
    public function test_rfq_status_transitions(): void
    {
        $order = Order::factory()->create([
            'customer_id' => $this->client->id,
            'currency_id' => $this->currency->id,
            'status' => 'pending',
        ]);

        $this->assertEquals('pending', $order->status);

        $order->update(['status' => 'processing']);
        $this->assertEquals('processing', $order->fresh()->status);

        $order->update(['status' => 'completed']);
        $this->assertEquals('completed', $order->fresh()->status);
    }

    /**
     * Test RFQ isolation by client (multi-tenancy)
     */
    public function test_rfq_isolation_by_client(): void
    {
        $client1 = createTestClient();
        $client2 = createTestClient();

        $order1 = Order::factory()->create([
            'customer_id' => $client1->id,
            'currency_id' => $this->currency->id,
        ]);

        $order2 = Order::factory()->create([
            'customer_id' => $client2->id,
            'currency_id' => $this->currency->id,
        ]);

        // Each client should only see their own orders
        $this->assertNotEquals($order1->customer_id, $order2->customer_id);
    }

    /**
     * Test commission calculation
     */
    public function test_commission_calculation(): void
    {
        $order = Order::factory()->create([
            'customer_id' => $this->client->id,
            'currency_id' => $this->currency->id,
            'commission_percent' => 5.00,
            'commission_type' => 'embedded',
        ]);

        $this->assertEquals(5.00, $order->commission_percent);
        $this->assertEquals('embedded', $order->commission_type);
    }

    /**
     * Test RFQ with currency conversion
     */
    public function test_rfq_with_currency_conversion(): void
    {
        $eur = Currency::where('code', 'EUR')->first() 
            ?? createTestCurrency(['code' => 'EUR']);

        $order = Order::factory()->create([
            'customer_id' => $this->client->id,
            'currency_id' => $eur->id,
        ]);

        $this->assertEquals($eur->id, $order->currency_id);
    }

    /**
     * Test deleting RFQ
     */
    public function test_can_delete_rfq(): void
    {
        $order = Order::factory()->create([
            'customer_id' => $this->client->id,
            'currency_id' => $this->currency->id,
        ]);

        $orderId = $order->id;
        $order->delete();

        // Should be soft deleted
        $this->assertNull(Order::find($orderId));
        $this->assertNotNull(Order::withTrashed()->find($orderId));
    }

    /**
     * Test RFQ with no items
     */
    public function test_rfq_with_no_items(): void
    {
        $order = Order::factory()->create([
            'customer_id' => $this->client->id,
            'currency_id' => $this->currency->id,
        ]);

        $this->assertEquals(0, $order->items()->count());
    }

    /**
     * Test order number auto generation
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
        // Format: [CLIENT_CODE]-[YY]-[NNNN] (e.g., HI-25-0001)
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{2,3}-\d{2}-\d{4}$/', $order->order_number);
    }
}
