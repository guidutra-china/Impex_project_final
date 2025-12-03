<?php

namespace Tests\Unit\Actions;

use App\Actions\RFQ\ImportRfqAction;
use App\Services\RFQImportService;
use App\Models\Order;
use Tests\TestCase;
use Mockery\MockInterface;

class ImportRfqActionTest extends TestCase
{
    private ImportRfqAction $action;
    private MockInterface $mockService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockService = $this->mock(RFQImportService::class);
        $this->action = new ImportRfqAction($this->mockService);
    }

    /**
     * Test that execute calls the service import method
     */
    public function test_execute_calls_service_import_method(): void
    {
        // Mock the Order instead of creating a real one
        $order = \Mockery::mock(Order::class);
        $order->id = 1;
        $filePath = '/tmp/test.xlsx';

        $expectedResult = [
            'success' => true,
            'message' => 'Successfully imported 5 items',
            'imported' => 5,
            'errors' => [],
        ];

        $this->mockService
            ->shouldReceive('importFromExcel')
            ->once()
            ->with($order, $filePath)
            ->andReturn($expectedResult);

        $result = $this->action->execute($order, $filePath);

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
        
        $filePath = '/tmp/test.xlsx';

        $this->action->handle($order, $filePath);
    }

    /**
     * Test that handle validates file exists
     */
    public function test_handle_validates_file_exists(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('File does not exist');

        // Create a mock Order that exists
        $order = \Mockery::mock(Order::class);
        $order->exists = true;
        $order->id = 1;
        
        $filePath = '/tmp/nonexistent_' . uniqid() . '.xlsx';

        $this->action->handle($order, $filePath);
    }

    /**
     * Test that handle calls execute when validation passes
     */
    public function test_handle_calls_execute_when_validation_passes(): void
    {
        // Create a temporary file
        $filePath = tempnam(sys_get_temp_dir(), 'test_');
        
        try {
            $order = \Mockery::mock(Order::class);
            $order->exists = true;
            $order->id = 1;

            $expectedResult = [
                'success' => true,
                'message' => 'Successfully imported 3 items',
                'imported' => 3,
                'errors' => [],
            ];

            $this->mockService
                ->shouldReceive('importFromExcel')
                ->once()
                ->with($order, $filePath)
                ->andReturn($expectedResult);

            $result = $this->action->handle($order, $filePath);

            $this->assertEquals($expectedResult, $result);
        } finally {
            // Cleanup
            @unlink($filePath);
        }
    }

    /**
     * Test that handle logs when service throws exception
     */
    public function test_handle_logs_when_service_throws_exception(): void
    {
        $filePath = tempnam(sys_get_temp_dir(), 'test_');
        
        try {
            $order = \Mockery::mock(Order::class);
            $order->exists = true;
            $order->id = 1;

            $this->mockService
                ->shouldReceive('importFromExcel')
                ->once()
                ->andThrow(new \Exception('Import failed'));

            $this->expectException(\Exception::class);
            $this->expectExceptionMessage('Import failed');

            $this->action->handle($order, $filePath);
        } finally {
            @unlink($filePath);
        }
    }
}
