<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\SupplierQuote;
use App\Models\QuoteItem;
use App\Models\OrderItem;
use App\Exceptions\RFQImportException;
use App\Exceptions\InvalidRowException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class SupplierQuoteImportService
{
    /**
     * Import supplier quote from Excel file
     *
     * @param SupplierQuote $supplierQuote
     * @param string $filePath
     * @return array Result with success status and message
     * @throws RFQImportException
     */
    public function importFromExcel(SupplierQuote $supplierQuote, string $filePath): array
    {
        // Set timeout for processing
        set_time_limit(SupplierQuoteImportConfig::PROCESSING_TIMEOUT);
        
        try {
            // File path is safe as it comes from Filament's FileUpload component
            if (!file_exists($filePath)) {
                throw new RFQImportException('File does not exist');
            }
            
            $worksheet = $this->loadAndValidateWorksheet($filePath);;
            
            // Extract RFQ number from Excel
            $rfqNumber = $this->extractRFQNumber($worksheet);
            
            // Validate RFQ matches the supplier quote's order
            if ($rfqNumber !== $supplierQuote->order->order_number) {
                throw new RFQImportException(
                    "RFQ number mismatch. Expected: {$supplierQuote->order->order_number}, Found: {$rfqNumber}"
                );
            }
            
            // Extract procurement details
            $procurementDetails = $this->extractProcurementDetails($worksheet);
            
            // Update supplier quote with procurement details
            $supplierQuote->moq = $procurementDetails['moq'];
            $supplierQuote->lead_time_days = $procurementDetails['lead_time_days'];
            $supplierQuote->incoterm = $procurementDetails['incoterm'];
            $supplierQuote->payment_terms = $procurementDetails['payment_terms'];
            $supplierQuote->save();
            
            // No need to validate supplier code - quote is already selected
            Log::info('Importing supplier quote', [
                'supplier_quote_id' => $supplierQuote->id,
                'supplier_id' => $supplierQuote->supplier_id,
                'supplier_name' => $supplierQuote->supplier->name,
                'procurement_details' => $procurementDetails,
            ]);
            
            $rows = $this->extractDataRows($worksheet);
            
            return $this->processRows($supplierQuote, $rows);
            
        } catch (RFQImportException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Supplier Quote Import Critical Error', [
                'supplier_quote_id' => $supplierQuote->id,
                'file' => basename($filePath),
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw new RFQImportException(
                'Import failed due to system error: ' . $e->getMessage(),
                ['supplier_quote_id' => $supplierQuote->id],
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
            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $reader->setReadEmptyCells(false);
            $spreadsheet = $reader->load($filePath);
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
     * Extract RFQ number from worksheet
     *
     * @param Worksheet $sheet
     * @return string
     * @throws RFQImportException
     */
    protected function extractRFQNumber(Worksheet $sheet): string
    {
        // Look for "RFQ Number:" in column A
        for ($row = 1; $row <= min(20, $sheet->getHighestRow()); $row++) {
            $cellValue = trim($sheet->getCell('A' . $row)->getValue() ?? '');
            
            if (stripos($cellValue, 'RFQ Number') !== false) {
                $rfqNumber = trim($sheet->getCell('B' . $row)->getValue() ?? '');
                
                if (empty($rfqNumber)) {
                    throw new RFQImportException('RFQ Number not found in Excel file');
                }
                
                return $rfqNumber;
            }
        }
        
        throw new RFQImportException('Could not find RFQ Number in Excel file');
    }

    /**
     * Extract Supplier Code from worksheet
     *
     * @param Worksheet $sheet
     * @return string|null
     */
    protected function extractSupplierCode(Worksheet $sheet): ?string
    {
        // Look for "Supplier Code:" in column A
        for ($row = 1; $row <= min(20, $sheet->getHighestRow()); $row++) {
            $cellValue = trim($sheet->getCell('A' . $row)->getValue() ?? '');
            
            if (stripos($cellValue, 'Supplier Code') !== false) {
                $supplierCode = trim($sheet->getCell('B' . $row)->getValue() ?? '');
                
                // Remove placeholder text
                if (stripos($supplierCode, '[To be filled') !== false) {
                    return null;
                }
                
                // Validate format (5 uppercase letters)
                if (preg_match('/^[A-Z]{5}$/', $supplierCode)) {
                    return $supplierCode;
                }
                
                Log::warning('Invalid supplier code format in Excel', ['code' => $supplierCode]);
                return null;
            }
        }
        
        return null;
    }

    /**
     * Extract data rows from worksheet
     *
     * @param Worksheet $sheet
     * @return Collection<SupplierQuoteImportRow>
     * @throws RFQImportException
     */
    protected function extractDataRows(Worksheet $sheet): Collection
    {
        $startRow = $this->findOrderItemsStartRow($sheet);
        
        if (!$startRow) {
            throw new RFQImportException(
                'Could not find "' . SupplierQuoteImportConfig::ORDER_ITEMS_HEADER . '" section in the Excel file'
            );
        }
        
        $dataStartRow = $startRow + SupplierQuoteImportConfig::HEADER_OFFSET;
        $actualHighestRow = $sheet->getHighestRow();
        $highestRow = min($actualHighestRow, $dataStartRow + SupplierQuoteImportConfig::MAX_ROWS - 1);
        
        if ($actualHighestRow > $highestRow) {
            Log::warning('Excel file exceeds maximum rows', [
                'actual_rows' => $actualHighestRow,
                'max_rows' => SupplierQuoteImportConfig::MAX_ROWS,
                'processing_up_to' => $highestRow,
            ]);
        }
        
        $rows = collect();
        
        for ($rowNumber = $dataStartRow; $rowNumber <= $highestRow; $rowNumber++) {
            $productName = trim($sheet->getCell(SupplierQuoteImportConfig::EXCEL_COLUMNS['product_name'] . $rowNumber)->getValue() ?? '');
            
            // Skip empty rows and invalid values
            if ($this->shouldSkipValue($productName)) {
                continue;
            }
            
            $quantity = $sheet->getCell(SupplierQuoteImportConfig::EXCEL_COLUMNS['quantity'] . $rowNumber)->getValue();
            $unitPrice = $sheet->getCell(SupplierQuoteImportConfig::EXCEL_COLUMNS['unit_price'] . $rowNumber)->getValue();
            $supplierPrice = $sheet->getCell(SupplierQuoteImportConfig::EXCEL_COLUMNS['supplier_price'] . $rowNumber)->getValue();
            
            // If supplier_price column is empty (RFQ without items), use unit_price as supplier_price
            if ($this->shouldSkipValue($supplierPrice) && !$this->shouldSkipValue($unitPrice)) {
                $supplierPrice = $unitPrice;
                $unitPrice = null; // No target price in this case
            }
            
            try {
                $rows->push(new SupplierQuoteImportRow(
                    productName: $productName,
                    quantity: (int) $quantity,
                    targetPrice: $this->parsePrice($unitPrice),
                    supplierPrice: $this->parsePrice($supplierPrice),
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
     * Process rows and create/update quote items
     *
     * @param SupplierQuote $supplierQuote
     * @param Collection<SupplierQuoteImportRow> $rows
     * @return array
     */
    protected function processRows(SupplierQuote $supplierQuote, Collection $rows): array
    {
        if ($rows->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No valid rows found to import',
                'imported' => 0,
                'errors' => [],
            ];
        }
        
        // Preload products and order items to avoid N+1 queries
        $productNames = $rows->pluck('productName')->unique()->toArray();
        $products = $this->batchLoadProducts($productNames);
        $orderItems = $this->batchLoadOrderItems($supplierQuote->order_id, $products->pluck('id')->toArray());
        
        $imported = 0;
        $errors = [];
        
        DB::beginTransaction();
        
        try {
            foreach ($rows as $row) {
                try {
                    // Skip rows without supplier price
                    if ($row->supplierPrice === null) {
                        continue;
                    }
                    
                    $product = $this->findProduct($row->productName, $products);
                    
                    // If product doesn't exist, create it with RFQ's category
                    if (!$product) {
                        $product = $this->createProduct($row->productName, $supplierQuote->order->category_id);
                        $products->put(strtolower($product->name), $product);
                    }
                    
                    $orderItem = $this->findOrderItem($product->id, $orderItems);
                    
                    // If order item doesn't exist, create it
                    if (!$orderItem) {
                        $orderItem = $this->createOrderItem($supplierQuote->order, $product, $row->quantity, $row->targetPrice);
                        $orderItems->put($product->id, $orderItem);
                    }
                    
                    $this->createOrUpdateQuoteItem($supplierQuote, $orderItem, $product, $row);
                    
                    $imported++;
                    
                } catch (InvalidRowException $e) {
                    $errors[] = $e->getMessage();
                }
            }
            
            DB::commit();
            
            // Recalculate commission after import
            $supplierQuote->calculateCommission();
            
            return [
                'success' => true,
                'message' => "Successfully imported {$imported} items" . 
                    (count($errors) > 0 ? " with " . count($errors) . " warnings." : "."),
                'imported' => $imported,
                'errors' => $errors,
            ];
            
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Unexpected error during batch import', [
                'supplier_quote_id' => $supplierQuote->id,
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
        $placeholders = implode(',', array_fill(0, count($normalizedNames), '?'));
        
        return Product::whereRaw("LOWER(name) IN ({$placeholders})", $normalizedNames)
            ->get()
            ->keyBy(fn($product) => strtolower($product->name));
    }

    /**
     * Batch load order items
     *
     * @param int $orderId
     * @param array $productIds
     * @return Collection<OrderItem>
     */
    protected function batchLoadOrderItems(int $orderId, array $productIds): Collection
    {
        return OrderItem::where('order_id', $orderId)
            ->whereIn('product_id', $productIds)
            ->get()
            ->keyBy('product_id');
    }

    /**
     * Find product in loaded collection
     *
     * @param string $productName
     * @param Collection $products
     * @return Product|null
     */
    protected function findProduct(string $productName, Collection $products): ?Product
    {
        return $products->get(strtolower($productName));
    }

    /**
     * Find order item in loaded collection
     *
     * @param int $productId
     * @param Collection $orderItems
     * @return OrderItem|null
     */
    protected function findOrderItem(int $productId, Collection $orderItems): ?OrderItem
    {
        return $orderItems->get($productId);
    }

    /**
     * Create or update quote item
     *
     * @param SupplierQuote $supplierQuote
     * @param OrderItem $orderItem
     * @param Product $product
     * @param SupplierQuoteImportRow $row
     */
    protected function createOrUpdateQuoteItem(
        SupplierQuote $supplierQuote,
        OrderItem $orderItem,
        Product $product,
        SupplierQuoteImportRow $row
    ): void {
        $existingItem = QuoteItem::where('supplier_quote_id', $supplierQuote->id)
            ->where('order_item_id', $orderItem->id)
            ->first();
        
        $data = [
            'supplier_quote_id' => $supplierQuote->id,
            'order_item_id' => $orderItem->id,
            'product_id' => $product->id,
            'quantity' => $row->quantity,
            'unit_price_before_commission' => $row->supplierPrice,
        ];
        
        if ($existingItem) {
            $existingItem->update($data);
        } else {
            QuoteItem::create($data);
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
            
            if (stripos($cellValue, SupplierQuoteImportConfig::ORDER_ITEMS_HEADER) !== false) {
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
    protected function shouldSkipValue(string|null $value): bool
    {
        if ($value === null) {
            return true;
        }
        return in_array(strtolower($value), SupplierQuoteImportConfig::SKIP_VALUES);
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
        
        if ($priceCents > SupplierQuoteImportConfig::MAX_PRICE) {
            Log::warning('Price exceeds maximum', ['price' => $priceFloat, 'max' => SupplierQuoteImportConfig::MAX_PRICE / 100]);
            return null;
        }
        
        return $priceCents;
    }

    /**
     * Create a new product
     *
     * @param string $productName
     * @param int|null $categoryId
     * @return Product
     */
    protected function createProduct(string $productName, ?int $categoryId = null): Product
    {
        Log::info('Creating new product from import', [
            'name' => $productName,
            'category_id' => $categoryId
        ]);
        
        // Generate unique SKU
        $sku = 'IMP-' . strtoupper(substr(md5($productName . microtime(true)), 0, 8));
        
        $productData = [
            'name' => $productName,
            'sku' => $sku,
            'status' => 'active',
            'type' => 'standard',
            'is_active' => true,
        ];
        
        // Add category if provided (from RFQ)
        if ($categoryId) {
            $productData['category_id'] = $categoryId;
        }
        
        $product = Product::create($productData);
        
        return $product;
    }

    /**
     * Create a new order item
     *
     * @param Order $order
     * @param Product $product
     * @param int $quantity
     * @param int|null $targetPrice
     * @return OrderItem
     */
    protected function createOrderItem(Order $order, Product $product, int $quantity, ?int $targetPrice): OrderItem
    {
        Log::info('Creating new order item from import', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
        ]);
        
        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'requested_unit_price' => $targetPrice,
        ]);
        
        return $orderItem;
    }

    /**
     * Extract procurement details from worksheet
     *
     * @param Worksheet $sheet
     * @return array
     */
    protected function extractProcurementDetails(Worksheet $sheet): array
    {
        $details = [
            'moq' => null,
            'lead_time_days' => null,
            'incoterm' => null,
            'payment_terms' => null,
        ];
        
        // Look for QUOTATION DETAILS section
        for ($row = 1; $row <= min(50, $sheet->getHighestRow()); $row++) {
            $cellValue = trim($sheet->getCell('A' . $row)->getValue() ?? '');
            
            // MOQ
            if (stripos($cellValue, 'MOQ') !== false && stripos($cellValue, 'Minimum Order Quantity') !== false) {
                $value = trim($sheet->getCell('B' . $row)->getValue() ?? '');
                $details['moq'] = is_numeric($value) ? (int) $value : null;
            }
            
            // Lead Time
            if (stripos($cellValue, 'Lead Time') !== false && stripos($cellValue, 'days') !== false) {
                $value = trim($sheet->getCell('B' . $row)->getValue() ?? '');
                $details['lead_time_days'] = is_numeric($value) ? (int) $value : null;
            }
            
            // Incoterm
            if (stripos($cellValue, 'Incoterm') !== false) {
                $value = trim($sheet->getCell('B' . $row)->getValue() ?? '');
                $details['incoterm'] = !empty($value) ? $value : null;
            }
            
            // Payment Terms
            if (stripos($cellValue, 'Payment Terms') !== false) {
                $value = trim($sheet->getCell('B' . $row)->getValue() ?? '');
                $details['payment_terms'] = !empty($value) ? $value : null;
            }
        }
        
        Log::info('Extracted procurement details', $details);
        
        return $details;
    }
}
