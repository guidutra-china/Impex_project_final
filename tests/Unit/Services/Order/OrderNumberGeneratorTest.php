<?php

namespace Tests\Unit\Services\Order;

use App\Models\Client;
use App\Models\Order;
use App\Services\Order\OrderNumberGenerator;
use Tests\TestCase;

class OrderNumberGeneratorTest extends TestCase
{
    private OrderNumberGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = app(OrderNumberGenerator::class);
    }

    /**
     * Test that generate returns correct format
     */
    public function test_generate_returns_correct_format(): void
    {
        $client = Client::factory()->create(['code' => 'AMA']);

        $orderNumber = $this->generator->generate($client);

        // Format should be [CODE]-[YY]-[NNNN]
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{2,3}-\d{2}-\d{4}$/', $orderNumber);
        $this->assertStringStartsWith('AMA-', $orderNumber);
    }

    /**
     * Test that generate throws exception for client without code
     */
    public function test_generate_throws_exception_for_client_without_code(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot generate order number: Client not found or has no code');

        $client = Client::factory()->create(['code' => null]);
        $this->generator->generate($client);
    }

    /**
     * Test that generate creates unique numbers
     */
    public function test_generate_creates_unique_numbers(): void
    {
        $client = Client::factory()->create(['code' => 'TEST']);

        $orderNumber1 = $this->generator->generate($client);
        Order::factory()->create(['order_number' => $orderNumber1, 'customer_id' => $client->id]);

        $orderNumber2 = $this->generator->generate($client);

        $this->assertNotEquals($orderNumber1, $orderNumber2);
        $this->assertStringStartsWith('TEST-', $orderNumber2);
    }

    /**
     * Test that get next sequential number returns correct value
     */
    public function test_get_next_sequential_number_returns_correct_value(): void
    {
        $client = Client::factory()->create(['code' => 'XYZ']);

        $nextNumber = $this->generator->getNextSequentialNumber($client);
        $this->assertEquals(1, $nextNumber);

        // Create an order
        $year = now()->format('y');
        Order::factory()->create([
            'order_number' => "XYZ-{$year}-0001",
            'customer_id' => $client->id,
        ]);

        $nextNumber = $this->generator->getNextSequentialNumber($client);
        $this->assertEquals(2, $nextNumber);
    }

    /**
     * Test that generate respects soft deleted orders
     */
    public function test_generate_respects_soft_deleted_orders(): void
    {
        $client = Client::factory()->create(['code' => 'DEL']);
        $year = now()->format('y');

        // Create and soft delete an order
        $order = Order::factory()->create([
            'order_number' => "DEL-{$year}-0001",
            'customer_id' => $client->id,
        ]);
        $order->delete();

        // Next generated number should skip the soft-deleted one
        $orderNumber = $this->generator->generate($client);
        $this->assertStringContainsString("DEL-{$year}-0002", $orderNumber);
    }
}
