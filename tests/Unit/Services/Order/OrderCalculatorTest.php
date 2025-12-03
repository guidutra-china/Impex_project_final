<?php

namespace Tests\Unit\Services\Order;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Order\OrderCalculator;
use Mockery;
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
        // Create mock items with commission percentages
        $item1 = Mockery::mock(OrderItem::class);
        $item1->quantity = 10;
        $item1->commission_percent = 5.0;

        $item2 = Mockery::mock(OrderItem::class);
        $item2->quantity = 20;
        $item2->commission_percent = 10.0;

        // Create mock order
        $order = Mockery::mock(Order::class)->makePartial();
        $order->items = collect([$item1, $item2]);

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
        // Create mock order with empty items
        $order = Mockery::mock(Order::class)->makePartial();
        $order->items = collect();

        $average = $this->calculator->calculateCommissionAverage($order);

        $this->assertNull($average);
    }

    /**
     * Test that get revenue returns selected quote price if available
     */
    public function test_get_revenue_returns_selected_quote_price(): void
    {
        // Create mock quote
        $quote = Mockery::mock();
        $quote->total_price_after_commission = 5000;

        // Create mock order with selected quote
        $order = Mockery::mock(Order::class)->makePartial();
        $order->total_amount = 1000;
        $order->selectedQuote = $quote;

        $revenue = $this->calculator->getRevenue($order);

        $this->assertEquals(5000, $revenue);
    }

    /**
     * Test that get revenue returns total amount if no selected quote
     */
    public function test_get_revenue_returns_total_amount_if_no_selected_quote(): void
    {
        // Create mock order without selected quote
        $order = Mockery::mock(Order::class)->makePartial();
        $order->total_amount = 3000;
        $order->selectedQuote = null;

        $revenue = $this->calculator->getRevenue($order);

        $this->assertEquals(3000, $revenue);
    }

    /**
     * Test that get revenue returns zero if no amount and no quote
     */
    public function test_get_revenue_returns_zero_if_no_amount_and_no_quote(): void
    {
        // Create mock order with no amount and no quote
        $order = Mockery::mock(Order::class)->makePartial();
        $order->total_amount = null;
        $order->selectedQuote = null;

        $revenue = $this->calculator->getRevenue($order);

        $this->assertEquals(0, $revenue);
    }

    /**
     * Test that get project expenses returns correct sum
     */
    public function test_get_project_expenses_returns_correct_sum(): void
    {
        // Create mock order
        $order = Mockery::mock(Order::class)->makePartial();

        // Mock the projectExpenses relationship
        $expensesMock = Mockery::mock();
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
        // Create mock order
        $order = Mockery::mock(Order::class)->makePartial();

        // Mock the projectExpenses relationship
        $expensesMock = Mockery::mock();
        $expensesMock->shouldReceive('sum')->with('amount_base_currency')->andReturn(10000);
        $order->shouldReceive('projectExpenses')->andReturn($expensesMock);

        $dollars = $this->calculator->getProjectExpensesDollars($order);

        $this->assertEquals(100.0, $dollars);
    }

    /**
     * Test that get purchase costs returns correct sum
     */
    public function test_get_purchase_costs_returns_correct_sum(): void
    {
        // Create mock order
        $order = Mockery::mock(Order::class)->makePartial();

        // Mock the purchaseOrders relationship
        $purchaseOrdersMock = Mockery::mock();
        $purchaseOrdersMock->shouldReceive('sum')->with('total')->andReturn(30000);
        $order->shouldReceive('purchaseOrders')->andReturn($purchaseOrdersMock);

        $costs = $this->calculator->getPurchaseCosts($order);

        $this->assertEquals(30000, $costs);
    }

    /**
     * Test that calculate real margin returns correct value
     */
    public function test_calculate_real_margin_returns_correct_value(): void
    {
        // Create mock order
        $order = Mockery::mock(Order::class)->makePartial();
        $order->total_amount = 100000;
        $order->selectedQuote = null;

        // Mock relationships
        $purchaseOrdersMock = Mockery::mock();
        $purchaseOrdersMock->shouldReceive('sum')->with('total')->andReturn(30000);
        $order->shouldReceive('purchaseOrders')->andReturn($purchaseOrdersMock);

        $expensesMock = Mockery::mock();
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
        // Create mock order
        $order = Mockery::mock(Order::class)->makePartial();
        $order->total_amount = 100000;
        $order->selectedQuote = null;

        // Mock relationships
        $purchaseOrdersMock = Mockery::mock();
        $purchaseOrdersMock->shouldReceive('sum')->with('total')->andReturn(30000);
        $order->shouldReceive('purchaseOrders')->andReturn($purchaseOrdersMock);

        $expensesMock = Mockery::mock();
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
        // Create mock order with zero revenue
        $order = Mockery::mock(Order::class)->makePartial();
        $order->total_amount = 0;
        $order->selectedQuote = null;

        $percentage = $this->calculator->calculateRealMarginPercentage($order);

        $this->assertEquals(0.0, $percentage);
    }
}
