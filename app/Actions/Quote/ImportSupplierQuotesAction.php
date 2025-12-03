<?php

namespace App\Actions\Quote;

use App\Models\Order;
use App\Services\SupplierQuoteImportService;
use Illuminate\Support\Facades\Log;

/**
 * ImportSupplierQuotesAction
 * 
 * Business logic action for importing supplier quotes from Excel files.
 * This action encapsulates the core business logic for quote import,
 * separate from UI concerns. It can be used in multiple contexts:
 * - Filament Resources (via Action::make())
 * - Controllers
 * - Jobs/Queues
 * - API endpoints
 * - Livewire Components
 * 
 * Filament V4 Pattern:
 * Actions in Filament V4 are primarily UI-centric, but this class
 * represents the underlying business logic that can be invoked from
 * Filament Actions or other contexts.
 * 
 * @example
 * // In a Filament Resource or Component:
 * $action = app(ImportSupplierQuotesAction::class);
 * $result = $action->execute($order, $filePath);
 * 
 * // Or via Filament Action:
 * Action::make('importQuotes')
 *     ->action(fn (ImportSupplierQuotesAction $action, Order $order, string $filePath) =>
 *         $action->execute($order, $filePath)
 *     )
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
     * Execute the supplier quote import
     * 
     * This is the main entry point for the action. It imports supplier quotes
     * from an Excel file into the order.
     * 
     * @param Order $order The order to import quotes for
     * @param string $filePath The path to the Excel file
     * @return array Result with success status and details
     */
    public function execute(Order $order, string $filePath): array
    {
        try {
            return $this->importService->importFromExcel($order, $filePath);
        } catch (\Exception $e) {
            Log::error('Supplier quote import failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Execute with validation
     * 
     * Use this method when you want to perform validation before import.
     * This is useful when called from Filament Actions where you might have
     * additional context.
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

        Log::info('Starting supplier quote import', [
            'order_id' => $order->id,
            'file' => basename($filePath),
            'options' => $options,
        ]);

        return $this->execute($order, $filePath);
    }

    /**
     * Get the status of quotes for an order
     * 
     * Convenience method to get information about the quotes for an order.
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
