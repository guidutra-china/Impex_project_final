<?php

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

class RFQImportService
{
    /**
     * Import products and prices from Excel file into RFQ
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
            $worksheet = $this->loadAndValidateWorksheet($filePath);
            $rows = $this->extractDataRows($worksheet);
            
            return $this->processRows($order, $rows);
            
        } catch (RFQImportException $e) {
            throw $e;
        } catch (\Throwable $e) {
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
        $realPath = realpath($filePath);
        $allowedDir = realpath(storage_path('app/temp/imports'));
        
        if (!$realPath || !$allowedDir) {
            throw new RFQImportException('Invalid file path');
        }
        
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
     * @return Collection<ImportRow>
     * @throws RFQImportException
     */
    protected function extractDataRows(Worksheet $sheet): Collection
    {
        $startRow = $this->findOrderItemsStartRow($sheet);
        
        if (!$startRow) {
            throw new RFQImportException(
                'Could not find "' . RFQImportConfig::ORDER_ITEMS_HEADER . '" section in the Excel file'
            );
        }
        
        $dataStartRow = $startRow + RFQImportConfig::HEADER_OFFSET;
        $actualHighestRow = $sheet->getHighestRow();
        $highestRow = min($actualHighestRow, $dataStartRow + RFQImportConfig::MAX_ROWS - 1);
        
        if ($actualHighestRow > $highestRow) {
            Log::warning('Excel file exceeds maximum rows', [
                'actual_rows' => $actualHighestRow,
                'max_rows' => RFQImportConfig::MAX_ROWS,
                'processing_up_to' => $highestRow,
            ]);
        }
        
        $rows = collect();
        
        for ($rowNumber = $dataStartRow; $rowNumber <= $highestRow; $rowNumber++) {
            $productName = trim($sheet->getCell(RFQImportConfig::EXCEL_COLUMNS['product_name'] . $rowNumber)->getValue() ?? '');
            
            // Skip empty rows and invalid values
            if ($this->shouldSkipValue($productName)) {
                continue;
            }
            
            $quantity = $sheet->getCell(RFQImportConfig::EXCEL_COLUMNS['quantity'] . $rowNumber)->getValue();
            $targetPrice = $sheet->getCell(RFQImportConfig::EXCEL_COLUMNS['target_price'] . $rowNumber)->getValue();
            
            try {
                $rows->push(new ImportRow(
                    productName: $productName,
                    quantity: (int) $quantity,
                    targetPrice: $this->parsePrice($targetPrice),
                    rowNumber: $rowNumber
                ));
            } catch (InvalidRowException $e) {
                // Log but continue processing other rows
                Log::warning('Invalid row skipped during import', $e->getContext());
            }
        }
        
        return $rows;
    }

    /**
     * Process rows and create/update order items
     *
     * @param Order $order
     * @param Collection<ImportRow> $rows
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
        
        // Preload products to avoid N+1 queries
        $productNames = $rows->pluck('productName')->unique()->toArray();
        $products = $this->batchLoadProducts($productNames);
        
        $imported = 0;
        $errors = [];
        
        DB::beginTransaction();
        
        try {
            foreach ($rows as $row) {
                try {
                    $product = $this->getOrCreateProduct($row->productName, $products);
                    
                    $this->createOrUpdateOrderItem($order, $product, $row);
                    
                    $imported++;
                    
                } catch (ProductCreationException $e) {
                    $errors[] = $e->getMessage();
                    Log::warning('Product creation failed during import', $e->getContext());
                } catch (InvalidRowException $e) {
                    $errors[] = $e->getMessage();
                }
            }
            
            DB::commit();
            
            return [
                'success' => true,
                'message' => "Successfully imported {$imported} items" . 
                    (count($errors) > 0 ? " with " . count($errors) . " errors." : "."),
                'imported' => $imported,
                'errors' => $errors,
            ];
            
        } catch (ProductCreationException $e) {
            DB::rollBack();
            Log::error('Product creation failed during batch import', [
                'order_id' => $order->id,
                'context' => $e->getContext(),
                'imported_before_error' => $imported,
            ]);
            throw $e;
        } catch (InvalidRowException $e) {
            DB::rollBack();
            Log::error('Invalid row data during batch import', [
                'order_id' => $order->id,
                'context' => $e->getContext(),
                'imported_before_error' => $imported,
            ]);
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Unexpected error during batch import', [
                'order_id' => $order->id,
                'imported_before_error' => $imported,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Batch load products by names to avoid N+1 queries
     *
     * @param array $productNames
     * @return Collection<Product>
     */
    protected function batchLoadProducts(array $productNames): Collection
    {
        // Normalize names to lowercase for case-insensitive search
        $normalizedNames = array_map('strtolower', $productNames);
        
        return Product::whereRaw('LOWER(name) IN (' . implode(',', array_fill(0, count($normalizedNames), '?')) . ')', $normalizedNames)
            ->get()
            ->keyBy(fn($product) => strtolower($product->name));
    }

    /**
     * Get existing product or create new one
     *
     * @param string $productName
     * @param Collection $loadedProducts
     * @return Product
     * @throws ProductCreationException
     */
    protected function getOrCreateProduct(string $productName, Collection $loadedProducts): Product
    {
        $key = strtolower($productName);
        
        if ($loadedProducts->has($key)) {
            return $loadedProducts->get($key);
        }
        
        // Use transaction with lock to prevent race conditions
        try {
            return DB::transaction(function () use ($productName, $loadedProducts, $key) {
                // Check again inside transaction to avoid race condition
                $existing = Product::whereRaw('LOWER(name) = ?', [strtolower($productName)])
                    ->lockForUpdate()
                    ->first();
                
                if ($existing) {
                    $loadedProducts->put($key, $existing);
                    return $existing;
                }
                
                // Generate unique SKU with retry logic
                $maxRetries = 5;
                $attempt = 0;
                $product = null;
                
                while ($attempt < $maxRetries && !$product) {
                    try {
                        $sku = RFQImportConfig::AUTO_SKU_PREFIX . strtoupper(substr(md5($productName . microtime(true) . random_int(1000, 9999) . $attempt), 0, 8));
                        
                        $product = Product::create([
                            'name' => $productName,
                            'sku' => $sku,
                            'status' => 'active',
                        ]);
                    } catch (\Illuminate\Database\QueryException $e) {
                        // Check if it's a unique constraint violation
                        if ($e->getCode() === '23000') {
                            $attempt++;
                            if ($attempt >= $maxRetries) {
                                throw new ProductCreationException(
                                    "Failed to generate unique SKU for product '{$productName}' after {$maxRetries} attempts",
                                    ['product_name' => $productName, 'attempts' => $attempt],
                                    0,
                                    $e
                                );
                            }
                            // Wait a bit before retry
                            usleep(10000); // 10ms
                        } else {
                            throw $e;
                        }
                    }
                }
                
                if (!$product) {
                    throw new ProductCreationException(
                        "Failed to create product '{$productName}'",
                        ['product_name' => $productName]
                    );
                }
                
                // Add to loaded products to avoid recreating
                $loadedProducts->put($key, $product);
                
                return $product;
            });
            
        } catch (\Exception $e) {
            throw new ProductCreationException(
                "Failed to create product '{$productName}': " . $e->getMessage(),
                ['product_name' => $productName],
                0,
                $e
            );
        }
    }

    /**
     * Create or update order item
     *
     * @param Order $order
     * @param Product $product
     * @param ImportRow $row
     */
    protected function createOrUpdateOrderItem(Order $order, Product $product, ImportRow $row): void
    {
        $existingItem = OrderItem::where('order_id', $order->id)
            ->where('product_id', $product->id)
            ->first();
        
        if ($existingItem) {
            $existingItem->update([
                'quantity' => $row->quantity,
                'requested_unit_price' => $row->targetPrice,
            ]);
        } else {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $row->quantity,
                'requested_unit_price' => $row->targetPrice,
            ]);
        }
    }

    /**
     * Find the row where "ORDER ITEMS" section starts
     *
     * @param Worksheet $sheet
     * @return int|null Row number or null if not found
     */
    protected function findOrderItemsStartRow(Worksheet $sheet): ?int
    {
        $highestRow = $sheet->getHighestRow();
        
        for ($row = 1; $row <= $highestRow; $row++) {
            $cellValue = trim($sheet->getCell('A' . $row)->getValue() ?? '');
            
            if (stripos($cellValue, RFQImportConfig::ORDER_ITEMS_HEADER) !== false) {
                return $row + 1;
            }
        }
        
        return null;
    }

    /**
     * Check if value should be skipped
     *
     * @param string $value
     * @return bool
     */
    protected function shouldSkipValue(string $value): bool
    {
        return in_array(strtolower($value), RFQImportConfig::SKIP_VALUES);
    }

    /**
     * Parse price from various formats
     *
     * @param mixed $price
     * @return int|null Price in cents
     */
    protected function parsePrice($price): ?int
    {
        // Validate type first
        if (!is_scalar($price)) {
            Log::warning('Invalid price type received', ['type' => gettype($price)]);
            return null;
        }
        
        if (empty($price) || $this->shouldSkipValue((string) $price)) {
            return null;
        }
        
        $priceStr = (string) $price;
        
        // Reject scientific notation to prevent overflow
        if (preg_match('/[eE]/', $priceStr)) {
            Log::warning('Scientific notation rejected in price', ['price' => $priceStr]);
            return null;
        }
        
        // Remove currency symbols and spaces
        $priceStr = preg_replace('/[^\d.,]/', '', $priceStr);
        
        // Replace comma with dot for decimal
        $priceStr = str_replace(',', '.', $priceStr);
        
        // Convert to float and then to cents
        $priceFloat = floatval($priceStr);
        
        if ($priceFloat < 0) {
            return null;
        }
        
        // Validate against maximum price
        $priceCents = (int) round($priceFloat * 100);
        
        if ($priceCents > RFQImportConfig::MAX_PRICE) {
            Log::warning('Price exceeds maximum', ['price' => $priceFloat, 'max' => RFQImportConfig::MAX_PRICE / 100]);
            return null;
        }
        
        return $priceCents;
    }
}
