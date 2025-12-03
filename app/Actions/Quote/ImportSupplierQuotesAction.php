<?php

namespace App\Actions\Quote;

use App\Models\Order;
use App\Services\SupplierQuoteImportService;
use Illuminate\Support\Facades\Log;

/**
 * ImportSupplierQuotesAction
 * 
 * Handles the import of supplier quotes from Excel files.
 * This action encapsulates the logic for validating, processing, and storing
 * supplier quotes for an order.
 * 
 * @example
 * $action = new ImportSupplierQuotesAction(new SupplierQuoteImportService());
 * $result = $action->execute($order, $filePath);
 */
class ImportSupplierQuotesAction
{
    /**
     * Create a new action instance
     */
    public function __construct(
        private SupplierQuoteImportService $importService
    ) {
    }

    /**
     * Execute the supplier quote import action
     * 
     * @param Order $order The order to import quotes for
     * @param string $filePath The path to the Excel file
     * @return array Result with success status and details
     */
    public function execute(Order $order, string $filePath): array
    {
        return $this->importService->importFromExcel($order, $filePath);
    }

    /**
     * Handle the supplier quote import with validation and logging
     * 
     * @param Order $order
     * @param string $filePath
     * @param array $options Additional options for the import
     * @return array
     */
    public function handle(Order $order, string $filePath, array $options = []): array
    {
        // Validate that the order exists
        if (!$order->exists) {
            throw new \Exception('Order does not exist');
        }

        // Validate file path
        if (!file_exists($filePath)) {
            throw new \Exception('File does not exist: ' . $filePath);
        }

        // Log the import attempt
        Log::info('Starting supplier quote import', [
            'order_id' => $order->id,
            'file' => basename($filePath),
            'options' => $options,
        ]);

        try {
            $result = $this->execute($order, $filePath);

            Log::info('Supplier quote import completed', [
                'order_id' => $order->id,
                'imported' => $result['imported'] ?? 0,
                'errors' => count($result['errors'] ?? []),
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('Supplier quote import failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get the status of quotes for an order
     * 
     * @param Order $order
     * @return array Status information about quotes
     */
    public function getQuoteStatus(Order $order): array
    {
        $quotes = $order->supplierQuotes()->get();

        return [
            'total_quotes' => $quotes->count(),
            'suppliers' => $quotes->pluck('supplier_name')->unique()->values()->toArray(),
            'latest_quote_date' => $quotes->max('created_at'),
        ];
    }
}
