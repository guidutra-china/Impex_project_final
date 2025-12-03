<?php

namespace Tests\Unit\Services\Order;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Order\OrderCalculator;
use Tests\TestCase;

class OrderCalculatorTest extends TestCase
{
    private OrderCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = app(OrderCalculator::class);
    }

    /**
     * Test that calculate commission average returns correct value
     */
    public function test_calculate_commission_average_returns_correct_value(): void
    {
        $order = Order::factory()->create();

        // Create items with different commission percentages
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'quantity' => 10,
            'commission_percent' => 5.0,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'quantity' => 20,
            'commission_percent' => 10.0,
        ]);

        $average = $this->calculator->calculateCommissionAverage($order);

        // Weighted average: (10*5 + 20*10) / (10+20) = 250/30 = 8.33
        $this->assertNotNull($average);
        $this->assertEqualsWithDelta(8.33, $average, 0.01);
    }

    /**
     * Test that calculate commission average returns null for empty items
     */
    public function test_calculate_commission_average_returns_null_for_empty_items(): void
    {
        $order = Order::factory()->create();

        $average = $this->calculator->calculateCommissionAverage($order);

        $this->assertNull($average);
    }

    /**
     * Test that get revenue returns selected quote price if available
     */
    public function test_get_revenue_returns_selected_quote_price(): void
    {
        $order = Order::factory()->create(['total_amount' => 1000]);
        $quote = \Mockery::mock();
        $quote->total_price_after_commission = 5000;
        $order->shouldReceive('selectedQuote')->andReturn($quote);

        $revenue = $this->calculator->getRevenue($order);

        $this->assertEquals(5000, $revenue);
    }

    /**
     * Test that get revenue returns total amount if no selected quote
     */
    public function test_get_revenue_returns_total_amount_if_no_selected_quote(): void
    {
        $order = Order::factory()->create(['total_amount' => 3000]);

        $revenue = $this->calculator->getRevenue($order);

        $this->assertEquals(3000, $revenue);
    }

    /**
     * Test that get project expenses returns correct sum
     */
    public function test_get_project_expenses_returns_correct_sum(): void
    {
        $order = Order::factory()->create();

        // Mock the projectExpenses relationship
        $expensesMock = \Mockery::mock();
        $expensesMock->shouldReceive('sum')->with('amount_base_currency')->andReturn(50000);
        $order->shouldReceive('projectExpenses')->andReturn($expensesMock);

        $expenses = $this->calculator->getProjectExpenses($order);

        $this->assertEquals(50000, $expenses);
    }

    /**
     * Test that get project expenses dollars converts correctly
     */
    public function test_get_project_expenses_dollars_converts_correctly(): void
    {
        $order = Order::factory()->create();

        // Mock the projectExpenses relationship
        $expensesMock = \Mockery::mock();
        $expensesMock->shouldReceive('sum')->with('amount_base_currency')->andReturn(10000);
        $order->shouldReceive('projectExpenses')->andReturn($expensesMock);

        $dollars = $this->calculator->getProjectExpensesDollars($order);

        $this->assertEquals(100.0, $dollars);
    }

    /**
     * Test that calculate real margin returns correct value
     */
    public function test_calculate_real_margin_returns_correct_value(): void
    {
        $order = Order::factory()->create(['total_amount' => 100000]);

        // Mock relationships
        $purchaseOrdersMock = \Mockery::mock();
        $purchaseOrdersMock->shouldReceive('sum')->with('total')->andReturn(30000);
        $order->shouldReceive('purchaseOrders')->andReturn($purchaseOrdersMock);

        $expensesMock = \Mockery::mock();
        $expensesMock->shouldReceive('sum')->with('amount_base_currency')->andReturn(10000);
        $order->shouldReceive('projectExpenses')->andReturn($expensesMock);

        $margin = $this->calculator->calculateRealMargin($order);

        // (100000 - 30000 - 10000) / 100 = 600
        $this->assertEquals(600.0, $margin);
    }

    /**
     * Test that calculate real margin percentage returns correct value
     */
    public function test_calculate_real_margin_percentage_returns_correct_value(): void
    {
        $order = Order::factory()->create(['total_amount' => 100000]);

        // Mock relationships
        $purchaseOrdersMock = \Mockery::mock();
        $purchaseOrdersMock->shouldReceive('sum')->with('total')->andReturn(30000);
        $order->shouldReceive('purchaseOrders')->andReturn($purchaseOrdersMock);

        $expensesMock = \Mockery::mock();
        $expensesMock->shouldReceive('sum')->with('amount_base_currency')->andReturn(10000);
        $order->shouldReceive('projectExpenses')->andReturn($expensesMock);

        $percentage = $this->calculator->calculateRealMarginPercentage($order);

        // Margin: 600, Revenue: 1000, Percentage: (600 / 1000) * 100 = 60%
        $this->assertEqualsWithDelta(60.0, $percentage, 0.01);
    }

    /**
     * Test that calculate real margin percentage returns 0 for zero revenue
     */
    public function test_calculate_real_margin_percentage_returns_zero_for_zero_revenue(): void
    {
        $order = Order::factory()->create(['total_amount' => 0]);

        $percentage = $this->calculator->calculateRealMarginPercentage($order);

        $this->assertEquals(0.0, $percentage);
    }
}
