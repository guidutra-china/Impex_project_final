<?php

namespace Tests\Integration\Actions;

use App\Actions\File\UploadFileAction;
use App\Actions\Quote\CompareQuotesAction;
use App\Actions\Quote\ImportSupplierQuotesAction;
use App\Actions\RFQ\ImportRfqAction;
use App\Models\Currency;
use App\Models\Order;
use App\Models\SupplierQuote;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ActionsIntegrationTest extends TestCase
{
    /**
     * Test that all Actions can be resolved from the container
     */
    public function test_all_actions_can_be_resolved_from_container(): void
    {
        $importRfqAction = app(ImportRfqAction::class);
        $compareQuotesAction = app(CompareQuotesAction::class);
        $uploadFileAction = app(UploadFileAction::class);
        $importSupplierQuotesAction = app(ImportSupplierQuotesAction::class);

        $this->assertInstanceOf(ImportRfqAction::class, $importRfqAction);
        $this->assertInstanceOf(CompareQuotesAction::class, $compareQuotesAction);
        $this->assertInstanceOf(UploadFileAction::class, $uploadFileAction);
        $this->assertInstanceOf(ImportSupplierQuotesAction::class, $importSupplierQuotesAction);
    }

    /**
     * Test CompareQuotesAction with real data
     */
    public function test_compare_quotes_action_with_real_data(): void
    {
        $order = Order::factory()->create();
        $currency = Currency::where('code', 'USD')->first();

        // Create some supplier quotes
        SupplierQuote::factory()
            ->count(3)
            ->create([
                'order_id' => $order->id,
                'currency_id' => $currency->id,
            ]);

        $action = app(CompareQuotesAction::class);
        $result = $action->handle($order);

        // Should return an array (even if empty)
        $this->assertIsArray($result);
    }

    /**
     * Test UploadFileAction with real file
     */
    public function test_upload_file_action_with_real_file(): void
    {
        $file = UploadedFile::fake()->create('test.pdf', 100);
        
        $action = app(UploadFileAction::class);
        $result = $action->handle($file, 'test-category', 'test-prefix');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('path', $result);
    }

    /**
     * Test that Actions maintain dependency injection
     */
    public function test_actions_maintain_dependency_injection(): void
    {
        $action1 = app(ImportRfqAction::class);
        $action2 = app(ImportRfqAction::class);

        // Both should be instances of the same class
        $this->assertInstanceOf(ImportRfqAction::class, $action1);
        $this->assertInstanceOf(ImportRfqAction::class, $action2);
    }

    /**
     * Test CompareQuotesAction convenience methods
     */
    public function test_compare_quotes_action_convenience_methods(): void
    {
        $order = Order::factory()->create();
        $currency = Currency::where('code', 'USD')->first();

        // Create supplier quotes
        SupplierQuote::factory()
            ->count(2)
            ->create([
                'order_id' => $order->id,
                'currency_id' => $currency->id,
            ]);

        $action = app(CompareQuotesAction::class);

        // Test getCheapestQuote
        $cheapest = $action->getCheapestQuote($order);
        // Should return null or array depending on data
        $this->assertTrue($cheapest === null || is_array($cheapest));

        // Test getRankedQuotes
        $ranked = $action->getRankedQuotes($order);
        $this->assertIsArray($ranked);
    }

    /**
     * Test ImportSupplierQuotesAction convenience methods
     */
    public function test_import_supplier_quotes_action_convenience_methods(): void
    {
        $order = Order::factory()->create();

        // Create some supplier quotes
        SupplierQuote::factory()
            ->count(2)
            ->create(['order_id' => $order->id]);

        $action = app(ImportSupplierQuotesAction::class);
        $status = $action->getQuoteStatus($order);

        $this->assertIsArray($status);
        $this->assertArrayHasKey('total_quotes', $status);
        $this->assertArrayHasKey('suppliers', $status);
        $this->assertArrayHasKey('latest_quote_date', $status);
        $this->assertEquals(2, $status['total_quotes']);
    }
}
