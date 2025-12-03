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

    public function test_execute_calls_service_compare_method(): void
    {
        $order = Order::factory()->create();

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

    public function test_handle_validates_order_exists(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Order does not exist');

        $order = new Order();

        $this->action->handle($order);
    }

    public function test_handle_returns_empty_array_when_no_quotes(): void
    {
        $order = Order::factory()->create();

        $result = $this->action->handle($order);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_get_cheapest_quote_returns_overall_quote(): void
    {
        $order = Order::factory()->create();

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

    public function test_get_cheapest_quote_returns_null_when_no_overall(): void
    {
        $order = Order::factory()->create();

        $this->mockService
            ->shouldReceive('compareQuotes')
            ->once()
            ->andReturn(['by_product' => []]);

        $result = $this->action->getCheapestQuote($order);

        $this->assertNull($result);
    }
}
