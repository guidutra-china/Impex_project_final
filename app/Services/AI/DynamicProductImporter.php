<?php

namespace App\Services\AI;

use App\Models\Product;
use App\Models\Currency;
use App\Models\Supplier;
use App\Models\Client;
use App\Models\Tag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DynamicProductImporter
{
    protected array $errors = [];
    protected array $warnings = [];
    protected int $successCount = 0;
    protected int $skippedCount = 0;
    protected int $updatedCount = 0;

    /**
     * Import products based on AI analysis and mapping
     *
     * @param array $analysis File analysis from AIFileAnalyzerService
     * @param array $mapping Column mapping (can be AI-suggested or user-adjusted)
     * @param array $options Additional import options
     * @return array
     */
    public function import(array $analysis, array $mapping, array $options = []): array
    {
        DB::beginTransaction();

        try {
            $rows = $analysis['all_rows'] ?? [];
            $images = $analysis['images'] ?? [];
            $supplierInfo = $options['supplier'] ?? null;
            $tags = $options['tags'] ?? [];
            $currency = $options['currency'] ?? 'USD';

            // Get or create supplier
            $supplier = null;
            if ($supplierInfo && !empty($supplierInfo['name'])) {
                $supplier = $this->getOrCreateSupplier($supplierInfo);
            }

            // Get currency
            $currencyModel = Currency::where('code', $currency)->first();

            // Get or create tags
            $tagModels = $this->getOrCreateTags($tags);

            foreach ($rows as $rowData) {
                $this->importRow($rowData, $mapping, $images, $supplier, $currencyModel, $tagModels);
            }

            DB::commit();

            return [
                'success' => count($this->errors) === 0,
                'message' => $this->buildResultMessage(),
                'stats' => [
                    'success' => $this->successCount,
                    'updated' => $this->updatedCount,
                    'skipped' => $this->skippedCount,
                    'errors' => count($this->errors),
                    'warnings' => count($this->warnings),
                ],
                'errors' => $this->errors,
                'warnings' => $this->warnings,
            ];

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Dynamic product import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Import a single row
     *
     * @param array $rowData
     * @param array $mapping
     * @param array $images
     * @param Supplier|null $supplier
     * @param Currency|null $currency
     * @param array $tags
     */
    protected function importRow(
        array $rowData,
        array $mapping,
        array $images,
        ?Supplier $supplier,
        ?Currency $currency,
        array $tags
    ): void {
        $rowNumber = $rowData['_row_number'] ?? 'unknown';

        try {
            // Map row data to product fields
            $productData = $this->mapRowToProduct($rowData, $mapping);

            // Validate required fields
            if (empty($productData['name'])) {
                throw new \Exception("Product name is required");
            }

            // Handle photo
            $photoPath = $this->handlePhoto($rowData, $mapping, $images);
            if ($photoPath) {
                $productData['avatar'] = $photoPath;
            }

            // Add supplier
            if ($supplier) {
                $productData['supplier_id'] = $supplier->id;
            }

            // Add currency
            if ($currency) {
                $productData['currency_id'] = $currency->id;
            }

            // Set defaults
            $productData['status'] = $productData['status'] ?? 'active';

            // Create or update product
            $product = $this->createOrUpdateProduct($productData);

            // Attach tags
            if (!empty($tags) && $product) {
                $product->tags()->sync(array_slice($tags, 0, 1)); // Only first tag as per system requirement
            }

            if ($product->wasRecentlyCreated) {
                $this->successCount++;
            } else {
                $this->updatedCount++;
            }

        } catch (\Throwable $e) {
            $this->errors[] = "Row {$rowNumber}: " . $e->getMessage();
            $this->skippedCount++;

            Log::warning('Failed to import product row', [
                'row' => $rowNumber,
                'error' => $e->getMessage(),
                'data' => $rowData,
            ]);
        }
    }

    /**
     * Map row data to product fields based on mapping
     *
     * @param array $rowData
     * @param array $mapping
     * @return array
     */
    protected function mapRowToProduct(array $rowData, array $mapping): array
    {
        $productData = [];

        foreach ($mapping as $column => $fieldInfo) {
            $field = is_array($fieldInfo) ? $fieldInfo['field'] : $fieldInfo;
            $value = $rowData[$column] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            // Skip special fields
            if (in_array($field, ['photo', 'photo_url', 'photo_embedded'])) {
                continue;
            }

            // Handle price conversion (convert to cents)
            if ($field === 'price') {
                $productData[$field] = (int) ($value * 100);
                continue;
            }

            // Handle numeric fields
            if (in_array($field, ['moq', 'lead_time_days', 'pcs_per_carton', 'pcs_per_inner_box'])) {
                $productData[$field] = (int) $value;
                continue;
            }

            // Handle decimal fields
            if (in_array($field, [
                'net_weight', 'gross_weight',
                'product_length', 'product_width', 'product_height',
                'carton_length', 'carton_width', 'carton_height', 'carton_weight', 'carton_cbm'
            ])) {
                $productData[$field] = (float) $value;
                continue;
            }

            // Handle dimensions extraction from description
            if ($field === 'description' && preg_match('/(\d+).*?×.*?(\d+).*?×.*?(\d+)/i', $value, $matches)) {
                $productData['product_length'] = (float) $matches[1];
                $productData['product_width'] = (float) $matches[2];
                $productData['product_height'] = (float) $matches[3];
            }

            // Handle weight extraction from description
            if ($field === 'description' && preg_match('/WT:?\s*(\d+)\s*kg/i', $value, $matches)) {
                $productData['gross_weight'] = (float) $matches[1];
            }

            $productData[$field] = $value;
        }

        return $productData;
    }

    /**
     * Handle photo for product
     *
     * @param array $rowData
     * @param array $mapping
     * @param array $images
     * @return string|null
     */
    protected function handlePhoto(array $rowData, array $mapping, array $images): ?string
    {
        $rowNumber = $rowData['_row_number'] ?? null;

        // Find photo column from mapping
        $photoColumn = null;
        foreach ($mapping as $column => $fieldInfo) {
            $field = is_array($fieldInfo) ? $fieldInfo['field'] : $fieldInfo;
            if (in_array($field, ['photo', 'photo_url', 'photo_embedded'])) {
                $photoColumn = $column;
                break;
            }
        }

        if (!$photoColumn) {
            return null;
        }

        // Check if there's an embedded image at this cell
        $cellCoordinate = $photoColumn . $rowNumber;
        if (isset($images[$cellCoordinate])) {
            // Move from temp to permanent storage
            $tempPath = $images[$cellCoordinate];
            $permanentPath = str_replace('import-temp', 'avatars', $tempPath);

            if (Storage::disk('public')->exists($tempPath)) {
                Storage::disk('public')->move($tempPath, $permanentPath);
                return $permanentPath;
            }
        }

        return null;
    }

    /**
     * Create or update product
     *
     * @param array $productData
     * @return Product
     */
    protected function createOrUpdateProduct(array $productData): Product
    {
        // Try to find existing product by SKU or name
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
     * Get or create supplier
     *
     * @param array $supplierInfo
     * @return Supplier|null
     */
    protected function getOrCreateSupplier(array $supplierInfo): ?Supplier
    {
        if (empty($supplierInfo['name'])) {
            return null;
        }

        $supplier = Supplier::where('name', $supplierInfo['name'])->first();

        if (!$supplier) {
            $supplier = Supplier::create([
                'name' => $supplierInfo['name'],
                'email' => $supplierInfo['email'] ?? null,
                'phone' => $supplierInfo['phone'] ?? null,
                'country' => $supplierInfo['country'] ?? null,
                'status' => 'active',
            ]);

            $this->warnings[] = "Created new supplier: {$supplierInfo['name']}";
        }

        return $supplier;
    }

    /**
     * Get or create tags
     *
     * @param array $tagNames
     * @return array
     */
    protected function getOrCreateTags(array $tagNames): array
    {
        $tagIds = [];

        foreach ($tagNames as $tagName) {
            if (empty($tagName)) {
                continue;
            }

            $tag = Tag::firstOrCreate(['name' => trim($tagName)]);
            $tagIds[] = $tag->id;
        }

        return $tagIds;
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
            $parts[] = "{$this->successCount} product(s) created";
        }

        if ($this->updatedCount > 0) {
            $parts[] = "{$this->updatedCount} product(s) updated";
        }

        if ($this->skippedCount > 0) {
            $parts[] = "{$this->skippedCount} row(s) skipped";
        }

        if (count($this->warnings) > 0) {
            $parts[] = count($this->warnings) . " warning(s)";
        }

        return implode(', ', $parts);
    }
}
