<?php

namespace App\Actions\RFQ;

use App\Models\Order;
use App\Services\RFQImportService;
use App\Exceptions\RFQImportException;
use Illuminate\Support\Facades\Log;

/**
 * ImportRfqAction
 * 
 * Business logic action for importing RFQ items from Excel files.
 * This action encapsulates the core business logic for RFQ import,
 * separate from UI concerns. It can be used in multiple contexts:
 * - Filament Resources (via Action::make())
 * - Controllers
 * - Jobs/Queues
 * - API endpoints
 * 
 * Filament V4 Pattern:
 * Actions in Filament V4 are primarily UI-centric, but this class
 * represents the underlying business logic that can be invoked from
 * Filament Actions or other contexts.
 * 
 * @example
 * // In a Filament Resource or Component:
 * $action = app(ImportRfqAction::class);
 * $result = $action->execute($order, $filePath);
 * 
 * // Or via Filament Action:
 * Action::make('import')
 *     ->action(fn (ImportRfqAction $action, Order $order, string $filePath) =>
 *         $action->execute($order, $filePath)
 *     )
 */
class ImportRfqAction
{
    /**
     * Create a new action instance
     */
    public function __construct(
        private RFQImportService $importService
    ) {
    }

    /**
     * Execute the RFQ import
     * 
     * This is the main entry point for the action. It performs the import
     * and returns the result.
     * 
     * @param Order $order The order to import items into
     * @param string $filePath The path to the Excel file
     * @return array Result with success status and details
     * @throws RFQImportException
     */
    public function execute(Order $order, string $filePath): array
    {
        try {
            return $this->importService->importFromExcel($order, $filePath);
        } catch (RFQImportException $e) {
            Log::error('RFQ import failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Execute with additional validation
     * 
     * Use this method when you want to perform additional validation
     * before calling the service. This is useful when called from
     * Filament Actions where you might have additional context.
     * 
     * @param Order $order
     * @param string $filePath
     * @param array $options Additional options for the import
     * @return array
     * @throws RFQImportException
     */
    public function handle(Order $order, string $filePath, array $options = []): array
    {
        // Validate that the order exists and is in a valid state
        if (!$order->exists) {
            throw new RFQImportException('Order does not exist');
        }

        // Validate file exists
        if (!file_exists($filePath)) {
            throw new RFQImportException('File does not exist: ' . $filePath);
        }

        Log::info('Starting RFQ import', [
            'order_id' => $order->id,
            'file' => basename($filePath),
            'options' => $options,
        ]);

        return $this->execute($order, $filePath);
    }
}
