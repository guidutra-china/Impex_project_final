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

    public function test_execute_calls_service_import_method(): void
    {
        $order = Order::factory()->create();
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

    public function test_handle_validates_order_exists(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Order does not exist');

        $order = new Order();
        $filePath = '/tmp/test.xlsx';

        $this->action->handle($order, $filePath);
    }

    public function test_handle_validates_file_exists(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('File does not exist');

        $order = Order::factory()->create();
        $filePath = '/tmp/nonexistent.xlsx';

        $this->action->handle($order, $filePath);
    }

    public function test_handle_logs_import_attempt(): void
    {
        $order = Order::factory()->create();
        $filePath = storage_path('app/temp/imports/test.xlsx');

        // Create the directory and file
        @mkdir(dirname($filePath), 0755, true);
        touch($filePath);

        $expectedResult = [
            'success' => true,
            'message' => 'Successfully imported 3 items',
            'imported' => 3,
            'errors' => [],
        ];

        $this->mockService
            ->shouldReceive('importFromExcel')
            ->once()
            ->andReturn($expectedResult);

        $result = $this->action->handle($order, $filePath);

        $this->assertEquals($expectedResult, $result);

        // Cleanup
        @unlink($filePath);
    }
}
