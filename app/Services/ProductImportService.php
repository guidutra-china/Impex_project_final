<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Currency;
use App\Models\Supplier;
use App\Models\Client;
use App\Models\Tag;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ProductImportService
{
    protected array $errors = [];
    protected array $warnings = [];
    protected int $successCount = 0;
    protected int $skippedCount = 0;

    /**
     * Import products from Excel file
     *
     * @param string $filePath
     * @return array Result with success status and message
     */
    public function importFromExcel(string $filePath): array
    {
        set_time_limit(ProductImportConfig::PROCESSING_TIMEOUT);
        
        try {
            if (!file_exists($filePath)) {
                throw new \Exception('File does not exist');
            }

            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();

            // Extract embedded images first (Option 2)
            $embeddedImages = $this->extractEmbeddedImages($worksheet);

            // Process data rows
            $rows = $this->extractDataRows($worksheet);

            return $this->processRows($rows, $embeddedImages);

        } catch (\Throwable $e) {
            Log::error('Product Import Error', [
                'file' => basename($filePath),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \Exception('Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Extract embedded images from worksheet (Option 2)
     *
     * @param Worksheet $worksheet
     * @return array Map of cell coordinates to image paths
     */
    protected function extractEmbeddedImages(Worksheet $worksheet): array
    {
        $images = [];

        foreach ($worksheet->getDrawingCollection() as $drawing) {
            if ($drawing instanceof Drawing) {
                try {
                    // Get the cell where the image is anchored
                    $coordinates = $drawing->getCoordinates();
                    
                    // Get image path
                    $imagePath = $drawing->getPath();
                    
                    if (file_exists($imagePath)) {
                        // Save image to storage
                        $savedPath = $this->saveImageFromFile($imagePath, $coordinates);
                        
                        if ($savedPath) {
                            $images[$coordinates] = $savedPath;
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('Failed to extract embedded image', [
                        'coordinates' => $drawing->getCoordinates() ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $images;
    }

    /**
     * Extract data rows from worksheet
     *
     * @param Worksheet $worksheet
     * @return array
     */
    protected function extractDataRows(Worksheet $worksheet): array
    {
        $rows = [];
        $highestRow = $worksheet->getHighestRow();

        for ($row = ProductImportConfig::DATA_START_ROW; $row <= $highestRow; $row++) {
            $rowData = [];
            $isEmpty = true;

            foreach (ProductImportConfig::COLUMN_MAPPINGS as $column => $field) {
                $value = $worksheet->getCell($column . $row)->getValue();
                
                if ($value !== null && $value !== '') {
                    $isEmpty = false;
                }
                
                $rowData[$field] = $value;
            }

            // Skip empty rows
            if (!$isEmpty) {
                $rowData['row_number'] = $row;
                $rows[] = $rowData;
            }
        }

        return $rows;
    }

    /**
     * Process all rows and create products
     *
     * @param array $rows
     * @param array $embeddedImages
     * @return array
     */
    protected function processRows(array $rows, array $embeddedImages): array
    {
        DB::beginTransaction();

        try {
            foreach ($rows as $rowData) {
                $this->processRow($rowData, $embeddedImages);
            }

            DB::commit();

            return [
                'success' => count($this->errors) === 0,
                'message' => $this->buildResultMessage(),
                'stats' => [
                    'success' => $this->successCount,
                    'skipped' => $this->skippedCount,
                    'errors' => count($this->errors),
                    'warnings' => count($this->warnings),
                ],
                'errors' => $this->errors,
                'warnings' => $this->warnings,
            ];

        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Process a single row
     *
     * @param array $rowData
     * @param array $embeddedImages
     */
    protected function processRow(array $rowData, array $embeddedImages): void
    {
        $rowNumber = $rowData['row_number'];

        try {
            // Validate required fields
            $this->validateRequiredFields($rowData, $rowNumber);

            // Prepare product data
            $productData = $this->prepareProductData($rowData);

            // Handle photo (Option 1: URL or Option 2: Embedded)
            $photoPath = $this->handlePhoto($rowData, $embeddedImages, $rowNumber);
            if ($photoPath) {
                $productData['avatar'] = $photoPath;
            }

            // Handle relationships
            $productData = $this->handleRelationships($productData, $rowData, $rowNumber);

            // Create or update product
            $product = $this->createOrUpdateProduct($productData, $rowData);

            // Handle tags
            $this->handleTags($product, $rowData, $rowNumber);

            $this->successCount++;

        } catch (\Throwable $e) {
            $this->errors[] = "Row {$rowNumber}: " . $e->getMessage();
            $this->skippedCount++;
            
            Log::warning('Product import row failed', [
                'row' => $rowNumber,
                'error' => $e->getMessage(),
                'data' => $rowData,
            ]);
        }
    }

    /**
     * Validate required fields
     *
     * @param array $rowData
     * @param int $rowNumber
     * @throws \Exception
     */
    protected function validateRequiredFields(array $rowData, int $rowNumber): void
    {
        foreach (ProductImportConfig::REQUIRED_FIELDS as $field) {
            if (empty($rowData[$field])) {
                throw new \Exception("Required field '{$field}' is missing");
            }
        }
    }

    /**
     * Prepare product data from row
     *
     * @param array $rowData
     * @return array
     */
    protected function prepareProductData(array $rowData): array
    {
        $data = [];

        foreach (ProductImportConfig::COLUMN_MAPPINGS as $column => $field) {
            // Skip special fields
            if (in_array($field, ['photo_url', 'photo_embedded', 'currency_code', 'supplier_name', 'customer_name', 'tags'])) {
                continue;
            }

            $value = $rowData[$field] ?? null;

            // Skip empty values
            if ($value === null || $value === '') {
                continue;
            }

            // Convert price to cents
            if (in_array($field, ProductImportConfig::PRICE_FIELDS)) {
                $data[$field] = (int) ($value * 100);
                continue;
            }

            // Convert to integer
            if (in_array($field, ProductImportConfig::INTEGER_FIELDS)) {
                $data[$field] = (int) $value;
                continue;
            }

            // Convert to decimal
            if (in_array($field, ProductImportConfig::DECIMAL_FIELDS)) {
                $data[$field] = (float) $value;
                continue;
            }

            // Validate status
            if ($field === 'status') {
                $value = strtolower($value);
                if (!in_array($value, ProductImportConfig::VALID_STATUSES)) {
                    $value = ProductImportConfig::DEFAULT_VALUES['status'];
                }
            }

            $data[$field] = $value;
        }

        // Set defaults
        foreach (ProductImportConfig::DEFAULT_VALUES as $field => $defaultValue) {
            if (!isset($data[$field])) {
                $data[$field] = $defaultValue;
            }
        }

        return $data;
    }

    /**
     * Handle photo import (Option 1: URL or Option 2: Embedded)
     *
     * @param array $rowData
     * @param array $embeddedImages
     * @param int $rowNumber
     * @return string|null
     */
    protected function handlePhoto(array $rowData, array $embeddedImages, int $rowNumber): ?string
    {
        // Option 2: Embedded image (priority)
        $embeddedColumn = 'H'; // Column H for embedded images
        $embeddedCell = $embeddedColumn . $rowNumber;
        
        if (isset($embeddedImages[$embeddedCell])) {
            return $embeddedImages[$embeddedCell];
        }

        // Option 1: URL
        $photoUrl = $rowData['photo_url'] ?? null;
        
        if ($photoUrl && filter_var($photoUrl, FILTER_VALIDATE_URL)) {
            try {
                return $this->downloadImageFromUrl($photoUrl);
            } catch (\Throwable $e) {
                $this->warnings[] = "Row {$rowNumber}: Failed to download image from URL: {$e->getMessage()}";
                Log::warning('Failed to download product image', [
                    'row' => $rowNumber,
                    'url' => $photoUrl,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return null;
    }

    /**
     * Download image from URL and save to storage (Option 1)
     *
     * @param string $url
     * @return string Storage path
     * @throws \Exception
     */
    protected function downloadImageFromUrl(string $url): string
    {
        $response = Http::timeout(30)->get($url);

        if (!$response->successful()) {
            throw new \Exception("Failed to download image: HTTP {$response->status()}");
        }

        $contentType = $response->header('Content-Type');
        $extension = $this->getExtensionFromContentType($contentType);

        if (!$extension) {
            throw new \Exception("Unsupported image format: {$contentType}");
        }

        $filename = Str::uuid() . '.' . $extension;
        $path = ProductImportConfig::IMAGE_STORAGE_PATH . '/' . $filename;

        Storage::disk('public')->put($path, $response->body());

        return $path;
    }

    /**
     * Save image from file (Option 2)
     *
     * @param string $filePath
     * @param string $coordinates
     * @return string|null Storage path
     */
    protected function saveImageFromFile(string $filePath, string $coordinates): ?string
    {
        try {
            $extension = pathinfo($filePath, PATHINFO_EXTENSION);
            
            if (!in_array(strtolower($extension), ProductImportConfig::SUPPORTED_IMAGE_FORMATS)) {
                return null;
            }

            $filename = Str::uuid() . '.' . $extension;
            $path = ProductImportConfig::IMAGE_STORAGE_PATH . '/' . $filename;

            $contents = file_get_contents($filePath);
            Storage::disk('public')->put($path, $contents);

            return $path;

        } catch (\Throwable $e) {
            Log::warning('Failed to save embedded image', [
                'coordinates' => $coordinates,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get file extension from content type
     *
     * @param string|null $contentType
     * @return string|null
     */
    protected function getExtensionFromContentType(?string $contentType): ?string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];

        return $map[$contentType] ?? null;
    }

    /**
     * Handle relationships (currency, supplier, customer)
     *
     * @param array $productData
     * @param array $rowData
     * @param int $rowNumber
     * @return array
     */
    protected function handleRelationships(array $productData, array $rowData, int $rowNumber): array
    {
        // Handle currency
        $currencyCode = $rowData['currency_code'] ?? ProductImportConfig::DEFAULT_VALUES['currency_code'];
        $currency = Currency::where('code', $currencyCode)->first();
        
        if ($currency) {
            $productData['currency_id'] = $currency->id;
        } else {
            $this->warnings[] = "Row {$rowNumber}: Currency '{$currencyCode}' not found, using default";
        }

        // Handle supplier
        if (!empty($rowData['supplier_name'])) {
            $supplier = Supplier::where('name', $rowData['supplier_name'])->first();
            if ($supplier) {
                $productData['supplier_id'] = $supplier->id;
            } else {
                $this->warnings[] = "Row {$rowNumber}: Supplier '{$rowData['supplier_name']}' not found";
            }
        }

        // Handle customer
        if (!empty($rowData['customer_name'])) {
            $customer = Client::where('name', $rowData['customer_name'])->first();
            if ($customer) {
                $productData['client_id'] = $customer->id;
            } else {
                $this->warnings[] = "Row {$rowNumber}: Customer '{$rowData['customer_name']}' not found";
            }
        }

        return $productData;
    }

    /**
     * Create or update product
     *
     * @param array $productData
     * @param array $rowData
     * @return Product
     */
    protected function createOrUpdateProduct(array $productData, array $rowData): Product
    {
        // Check if product exists by SKU or name
        $existingProduct = null;
        
        if (!empty($productData['sku'])) {
            $existingProduct = Product::where('sku', $productData['sku'])->first();
        }
        
        if (!$existingProduct && !empty($productData['name'])) {
            $existingProduct = Product::where('name', $productData['name'])->first();
        }

        if ($existingProduct) {
            $existingProduct->update($productData);
            return $existingProduct;
        }

        return Product::create($productData);
    }

    /**
     * Handle tags
     *
     * @param Product $product
     * @param array $rowData
     * @param int $rowNumber
     */
    protected function handleTags(Product $product, array $rowData, int $rowNumber): void
    {
        $tagsString = $rowData['tags'] ?? '';
        
        if (empty($tagsString)) {
            throw new \Exception("Tags are required");
        }

        $tagNames = array_map('trim', explode(',', $tagsString));
        $tagIds = [];

        foreach ($tagNames as $tagName) {
            if (empty($tagName)) {
                continue;
            }

            $tag = Tag::firstOrCreate(['name' => $tagName]);
            $tagIds[] = $tag->id;
        }

        if (empty($tagIds)) {
            throw new \Exception("At least one valid tag is required");
        }

        // Sync tags (limit to 1 as per ProductForm requirement)
        $product->tags()->sync(array_slice($tagIds, 0, 1));
    }

    /**
     * Build result message
     *
     * @return string
     */
    protected function buildResultMessage(): string
    {
        $parts = [];

        if ($this->successCount > 0) {
            $parts[] = "{$this->successCount} product(s) imported successfully";
        }

        if ($this->skippedCount > 0) {
            $parts[] = "{$this->skippedCount} row(s) skipped due to errors";
        }

        if (count($this->warnings) > 0) {
            $parts[] = count($this->warnings) . " warning(s)";
        }

        return implode(', ', $parts);
    }
}
