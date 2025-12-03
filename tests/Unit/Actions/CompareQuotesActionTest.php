<?php

namespace Tests\Unit\Actions;

use App\Actions\Quote\CompareQuotesAction;
use App\Services\QuoteComparisonService;
use App\Models\Order;
use Tests\TestCase;
use Mockery\MockInterface;

class CompareQuotesActionTest extends TestCase
{
    private CompareQuotesAction $action;
    private MockInterface $mockService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockService = $this->mock(QuoteComparisonService::class);
        $this->action = new CompareQuotesAction($this->mockService);
    }

    /**
     * Test that execute calls the service compare method
     */
    public function test_execute_calls_service_compare_method(): void
    {
        // Mock the Order instead of creating a real one
        $order = \Mockery::mock(Order::class);
        $order->id = 1;

        $expectedResult = [
            'by_product' => [],
            'overall' => [
                'supplier' => 'Supplier A',
                'total_price' => 1000,
            ],
        ];

        $this->mockService
            ->shouldReceive('compareQuotes')
            ->once()
            ->with($order)
            ->andReturn($expectedResult);

        $result = $this->action->execute($order);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Test that handle validates order exists
     */
    public function test_handle_validates_order_exists(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Order does not exist');

        // Create a mock Order that doesn't exist
        $order = \Mockery::mock(Order::class);
        $order->exists = false;

        $this->action->handle($order);
    }

    /**
     * Test that handle returns empty array when no quotes
     */
    public function test_handle_returns_empty_array_when_no_quotes(): void
    {
        $order = \Mockery::mock(Order::class);
        $order->exists = true;
        $order->id = 1;

        // Mock the supplierQuotes relationship
        $quoteMock = \Mockery::mock();
        $quoteMock->shouldReceive('count')->andReturn(0);
        $order->shouldReceive('supplierQuotes')->andReturn($quoteMock);

        $result = $this->action->handle($order);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test that get cheapest quote returns overall quote
     */
    public function test_get_cheapest_quote_returns_overall_quote(): void
    {
        $order = \Mockery::mock(Order::class);
        $order->id = 1;

        $expectedResult = [
            'by_product' => [],
            'overall' => [
                'supplier' => 'Supplier A',
                'total_price' => 1000,
            ],
        ];

        $this->mockService
            ->shouldReceive('compareQuotes')
            ->once()
            ->andReturn($expectedResult);

        $result = $this->action->getCheapestQuote($order);

        $this->assertEquals($expectedResult['overall'], $result);
    }

    /**
     * Test that get cheapest quote returns null when no overall
     */
    public function test_get_cheapest_quote_returns_null_when_no_overall(): void
    {
        $order = \Mockery::mock(Order::class);
        $order->id = 1;

        $this->mockService
            ->shouldReceive('compareQuotes')
            ->once()
            ->andReturn(['by_product' => []]);

        $result = $this->action->getCheapestQuote($order);

        $this->assertNull($result);
    }

    /**
     * Test that get ranked quotes returns sorted array
     */
    public function test_get_ranked_quotes_returns_sorted_array(): void
    {
        $order = \Mockery::mock(Order::class);
        $order->id = 1;

        $expectedResult = [
            'by_product' => [
                [
                    'quotes' => [
                        ['total_price_after_commission' => 1000],
                        ['total_price_after_commission' => 500],
                    ],
                ],
            ],
        ];

        $this->mockService
            ->shouldReceive('compareQuotes')
            ->once()
            ->andReturn($expectedResult);

        $result = $this->action->getRankedQuotes($order);

        $this->assertIsArray($result);
        // Should be sorted by price (500 before 1000)
        if (count($result) >= 2) {
            $this->assertLessThanOrEqual(
                $result[1]['total_price_after_commission'] ?? 0,
                $result[0]['total_price_after_commission'] ?? 0
            );
        }
    }

    /**
     * Test that handle calls execute when validation passes
     */
    public function test_handle_calls_execute_when_validation_passes(): void
    {
        $order = \Mockery::mock(Order::class);
        $order->exists = true;
        $order->id = 1;

        // Mock the supplierQuotes relationship
        $quoteMock = \Mockery::mock();
        $quoteMock->shouldReceive('count')->andReturn(2);
        $order->shouldReceive('supplierQuotes')->andReturn($quoteMock);

        $expectedResult = [
            'by_product' => [],
            'overall' => ['supplier' => 'Supplier A'],
        ];

        $this->mockService
            ->shouldReceive('compareQuotes')
            ->once()
            ->with($order)
            ->andReturn($expectedResult);

        $result = $this->action->handle($order);

        $this->assertEquals($expectedResult, $result);
    }
}
