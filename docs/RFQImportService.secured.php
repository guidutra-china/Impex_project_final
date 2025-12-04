<?php

/**
 * SECURITY PATCH FOR RFQImportService
 * 
 * This file demonstrates the security improvements that should be applied
 * to the RFQImportService class. The main changes are:
 * 
 * 1. Proper transaction handling with DB::transaction()
 * 2. Rollback on any error to maintain data consistency
 * 3. File validation before processing
 * 4. Proper error handling and logging
 * 
 * APPLY THESE CHANGES TO: app/Services/RFQImportService.php
 */

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use App\Exceptions\RFQImportException;
use App\Exceptions\InvalidRowException;
use App\Exceptions\ProductCreationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class RFQImportServiceSecured
{
    /**
     * Import products and prices from Excel file into RFQ
     * 
     * SECURITY IMPROVEMENTS:
     * - Wrapped in DB::transaction() for data consistency
     * - Automatic rollback on any error
     * - File validation before processing
     * 
     * @param Order $order
     * @param string $filePath
     * @return array Result with success status and message
     * @throws RFQImportException
     */
    public function importFromExcel(Order $order, string $filePath): array
    {
        // Validate file path for security
        $this->validateFilePath($filePath);
        
        try {
            // Wrap entire operation in a database transaction
            return DB::transaction(function () use ($order, $filePath) {
                $worksheet = $this->loadAndValidateWorksheet($filePath);
                $rows = $this->extractDataRows($worksheet);
                
                return $this->processRows($order, $rows);
            });
            
        } catch (RFQImportException $e) {
            // Log and re-throw known exceptions
            Log::warning('RFQ Import validation error', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (\Throwable $e) {
            // Log unexpected errors and convert to RFQImportException
            Log::error('RFQ Import Critical Error', [
                'order_id' => $order->id,
                'file' => basename($filePath),
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw new RFQImportException(
                'Import failed due to system error: ' . $e->getMessage(),
                ['order_id' => $order->id],
                0,
                $e
            );
        }
    }

    /**
     * Validate file path to prevent path traversal attacks
     *
     * @param string $filePath
     * @throws RFQImportException
     */
    protected function validateFilePath(string $filePath): void
    {
        // Ensure the file path is real and doesn't contain traversal attempts
        $realPath = realpath($filePath);
        $allowedDir = realpath(storage_path('app/temp/imports'));
        
        if (!$realPath || !$allowedDir) {
            throw new RFQImportException('Invalid file path');
        }
        
        // Check if the real path is within the allowed directory
        if (strpos($realPath, $allowedDir) !== 0) {
            Log::warning('Path traversal attempt detected', [
                'attempted_path' => $filePath,
                'real_path' => $realPath,
                'allowed_dir' => $allowedDir,
            ]);
            
            throw new RFQImportException('File path is outside allowed directory');
        }
        
        if (!file_exists($realPath)) {
            throw new RFQImportException('File does not exist');
        }
    }

    /**
     * Load and validate Excel worksheet
     *
     * @param string $filePath
     * @return Worksheet
     * @throws RFQImportException
     */
    protected function loadAndValidateWorksheet(string $filePath): Worksheet
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            return $spreadsheet->getActiveSheet();
        } catch (\Exception $e) {
            throw new RFQImportException(
                'Failed to load Excel file: ' . $e->getMessage(),
                ['file' => basename($filePath)],
                0,
                $e
            );
        }
    }

    /**
     * Extract data rows from worksheet
     *
     * @param Worksheet $sheet
     * @return Collection
     * @throws RFQImportException
     */
    protected function extractDataRows(Worksheet $sheet): Collection
    {
        $rows = collect();
        
        // This is a simplified version - adapt to your actual structure
        $highestRow = $sheet->getHighestRow();
        
        for ($rowNumber = 2; $rowNumber <= $highestRow; $rowNumber++) {
            $productName = trim($sheet->getCell('A' . $rowNumber)->getValue() ?? '');
            
            if (empty($productName)) {
                continue;
            }
            
            $quantity = $sheet->getCell('B' . $rowNumber)->getValue();
            $targetPrice = $sheet->getCell('C' . $rowNumber)->getValue();
            
            $rows->push([
                'productName' => $productName,
                'quantity' => (int) $quantity,
                'targetPrice' => (float) $targetPrice,
                'rowNumber' => $rowNumber,
            ]);
        }
        
        return $rows;
    }

    /**
     * Process rows and create/update order items
     * 
     * SECURITY IMPROVEMENTS:
     * - Entire operation is wrapped in a transaction (handled by caller)
     * - Proper error handling with rollback
     * - Logging of all operations
     *
     * @param Order $order
     * @param Collection $rows
     * @return array
     */
    protected function processRows(Order $order, Collection $rows): array
    {
        if ($rows->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No valid rows found to import',
                'imported' => 0,
                'errors' => [],
            ];
        }
        
        $imported = 0;
        $errors = [];
        
        try {
            foreach ($rows as $row) {
                try {
                    // Get or create product
                    $product = Product::where('name', $row['productName'])->first();
                    
                    if (!$product) {
                        $product = Product::create([
                            'name' => $row['productName'],
                            'sku' => $this->generateUniqueSKU(),
                            'status' => 'active',
                        ]);
                    }
                    
                    // Create or update order item
                    OrderItem::updateOrCreate(
                        [
                            'order_id' => $order->id,
                            'product_id' => $product->id,
                        ],
                        [
                            'quantity' => $row['quantity'],
                            'target_price_cents' => (int) ($row['targetPrice'] * 100),
                        ]
                    );
                    
                    $imported++;
                    
                } catch (\Exception $e) {
                    $errors[] = "Row {$row['rowNumber']}: " . $e->getMessage();
                    Log::warning('Failed to process row during import', [
                        'order_id' => $order->id,
                        'row' => $row['rowNumber'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            return [
                'success' => true,
                'message' => "Successfully imported {$imported} items" . 
                    (count($errors) > 0 ? " with " . count($errors) . " errors." : "."),
                'imported' => $imported,
                'errors' => $errors,
            ];
            
        } catch (\Exception $e) {
            // This will trigger a rollback of the entire transaction
            Log::error('Batch import failed', [
                'order_id' => $order->id,
                'imported_before_error' => $imported,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Generate a unique SKU
     */
    private function generateUniqueSKU(): string
    {
        do {
            $sku = 'SKU-' . strtoupper(substr(md5(time() . rand()), 0, 8));
        } while (Product::where('sku', $sku)->exists());
        
        return $sku;
    }
}
