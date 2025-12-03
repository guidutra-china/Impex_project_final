<?php

namespace App\Actions\RFQ;

use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use App\Exceptions\RFQImportException;
use App\Exceptions\InvalidRowException;
use App\Exceptions\ProductCreationException;
use App\Services\RFQImportService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ImportRfqAction
 * 
 * Handles the import of products and prices from Excel files into an RFQ (Order).
 * This action encapsulates the entire import workflow, including validation, processing,
 * and error handling.
 * 
 * @example
 * $action = new ImportRfqAction(new RFQImportService());
 * $result = $action->execute($order, $filePath);
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
     * Execute the import action
     * 
     * @param Order $order The order to import items into
     * @param string $filePath The path to the Excel file
     * @return array Result with success status and details
     * @throws RFQImportException
     */
    public function execute(Order $order, string $filePath): array
    {
        return $this->importService->importFromExcel($order, $filePath);
    }

    /**
     * Handle the import with additional validation
     * 
     * This method can be used when you want to perform additional validation
     * before calling the service.
     * 
     * @param Order $order
     * @param string $filePath
     * @param array $options Additional options for the import
     * @return array
     * @throws RFQImportException
     */
    public function handle(Order $order, string $filePath, array $options = []): array
    {
        // Validate that the order exists and is in a valid state for import
        if (!$order->exists) {
            throw new RFQImportException('Order does not exist');
        }

        // Validate file path
        if (!file_exists($filePath)) {
            throw new RFQImportException('File does not exist: ' . $filePath);
        }

        // Log the import attempt
        Log::info('Starting RFQ import', [
            'order_id' => $order->id,
            'file' => basename($filePath),
            'options' => $options,
        ]);

        try {
            $result = $this->execute($order, $filePath);

            Log::info('RFQ import completed', [
                'order_id' => $order->id,
                'imported' => $result['imported'] ?? 0,
                'errors' => count($result['errors'] ?? []),
            ]);

            return $result;
        } catch (RFQImportException $e) {
            Log::error('RFQ import failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
