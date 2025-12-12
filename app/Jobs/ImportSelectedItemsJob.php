<?php

namespace App\Jobs;

use App\Models\ImportHistory;
use App\Models\ImportPreviewItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Tag;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportSelectedItemsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800; // 30 minutes
    public int $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ImportHistory $importHistory
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting selected items import', [
                'import_id' => $this->importHistory->id,
            ]);

            // Update status
            $this->importHistory->update([
                'status' => 'importing',
            ]);

            // Get selected items
            $selectedItems = $this->importHistory->previewItems()
                ->where('selected', true)
                ->get();

            if ($selectedItems->isEmpty()) {
                throw new \Exception('No items selected for import');
            }

            $stats = [
                'success' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => 0,
            ];

            $errors = [];
            $warnings = [];

            // Get or create supplier
            $supplier = $this->getOrCreateSupplier();

            // Process each item
            foreach ($selectedItems as $item) {
                try {
                    $result = $this->processItem($item, $supplier);
                    
                    if ($result['success']) {
                        if ($result['action'] === 'updated') {
                            $stats['updated']++;
                        } else {
                            $stats['success']++;
                        }
                    } else {
                        $stats['skipped']++;
                        if (!empty($result['error'])) {
                            $errors[] = "Row {$item->row_number}: {$result['error']}";
                        }
                    }

                    if (!empty($result['warning'])) {
                        $warnings[] = "Row {$item->row_number}: {$result['warning']}";
                    }

                } catch (\Throwable $e) {
                    Log::error('Failed to import item', [
                        'import_id' => $this->importHistory->id,
                        'item_id' => $item->id,
                        'row' => $item->row_number,
                        'error' => $e->getMessage(),
                    ]);
                    
                    $stats['errors']++;
                    $errors[] = "Row {$item->row_number}: {$e->getMessage()}";
                }
            }

            // Update import history
            $this->importHistory->update([
                'status' => 'completed',
                'success_count' => $stats['success'],
                'updated_count' => $stats['updated'],
                'skipped_count' => $stats['skipped'],
                'error_count' => $stats['errors'],
                'warning_count' => count($warnings),
                'errors' => $errors,
                'warnings' => $warnings,
                'result_message' => $this->generateResultMessage($stats),
                'imported_at' => now(),
            ]);

            Log::info('Import completed successfully', [
                'import_id' => $this->importHistory->id,
                'stats' => $stats,
            ]);

        } catch (\Throwable $e) {
            Log::error('Import failed', [
                'import_id' => $this->importHistory->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->importHistory->update([
                'status' => 'failed',
                'result_message' => 'Import failed: ' . $e->getMessage(),
                'errors' => [
                    'import_error' => $e->getMessage(),
                ],
            ]);

            throw $e;
        }
    }

    /**
     * Process single preview item
     */
    protected function processItem(ImportPreviewItem $item, ?Supplier $supplier): array
    {
        // Check action
        if ($item->action === 'skip') {
            return [
                'success' => false,
                'action' => 'skipped',
                'error' => 'Marked to skip',
            ];
        }

        // Validate required fields
        if (empty($item->name)) {
            return [
                'success' => false,
                'action' => 'skipped',
                'error' => 'Product name is required',
            ];
        }

        DB::beginTransaction();
        
        try {
            $product = null;
            $action = 'created';

            // Handle different actions
            switch ($item->action) {
                case 'update':
                    if ($item->existing_product_id) {
                        $product = Product::find($item->existing_product_id);
                        if ($product) {
                            $this->updateProduct($product, $item);
                            $action = 'updated';
                        }
                    }
                    break;

                case 'merge':
                    if ($item->existing_product_id) {
                        $product = Product::find($item->existing_product_id);
                        if ($product) {
                            $this->mergeProduct($product, $item);
                            $action = 'updated';
                        }
                    }
                    break;

                case 'import':
                default:
                    $product = $this->createProduct($item);
                    $action = 'created';
                    break;
            }

            if (!$product) {
                throw new \Exception('Failed to create or update product');
            }

            // Link supplier
            if ($supplier) {
                $product->suppliers()->syncWithoutDetaching([$supplier->id]);
            }

            // Handle photo
            $photoWarning = $this->handlePhoto($product, $item);

            // Handle features
            if (!empty($item->features)) {
                $this->handleFeatures($product, $item->features);
            }

            DB::commit();

            return [
                'success' => true,
                'action' => $action,
                'product_id' => $product->id,
                'warning' => $photoWarning,
            ];

        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create new product
     */
    protected function createProduct(ImportPreviewItem $item): Product
    {
        return Product::create([
            'sku' => $item->sku ?: $this->generateSKU(),
            'supplier_code' => $item->supplier_code,
            'model_number' => $item->model_number,
            'name' => $item->name,
            'description' => $item->description,
            'price' => $item->price,
            'brand' => $item->brand,
            'gross_weight' => $item->gross_weight,
            'net_weight' => $item->net_weight,
            'product_length' => $item->product_length,
            'product_width' => $item->product_width,
            'product_height' => $item->product_height,
            'carton_length' => $item->carton_length,
            'carton_width' => $item->carton_width,
            'carton_height' => $item->carton_height,
            'carton_weight' => $item->carton_weight,
            'carton_cbm' => $item->carton_cbm,
            'pcs_per_carton' => $item->pcs_per_carton,
            'pcs_per_inner_box' => $item->pcs_per_inner_box,
            'moq' => $item->moq,
            'lead_time_days' => $item->lead_time_days,
            'hs_code' => $item->hs_code,
            'certifications' => $item->certifications,
        ]);
    }

    /**
     * Update existing product
     */
    protected function updateProduct(Product $product, ImportPreviewItem $item): void
    {
        $product->update([
            'sku' => $item->sku ?: $product->sku,
            'supplier_code' => $item->supplier_code ?: $product->supplier_code,
            'model_number' => $item->model_number ?: $product->model_number,
            'name' => $item->name,
            'description' => $item->description ?: $product->description,
            'price' => $item->price ?: $product->price,
            'brand' => $item->brand ?: $product->brand,
            'gross_weight' => $item->gross_weight ?: $product->gross_weight,
            'net_weight' => $item->net_weight ?: $product->net_weight,
            'moq' => $item->moq ?: $product->moq,
            'lead_time_days' => $item->lead_time_days ?: $product->lead_time_days,
        ]);
    }

    /**
     * Merge product data (keep existing if new is empty)
     */
    protected function mergeProduct(Product $product, ImportPreviewItem $item): void
    {
        $updates = [];
        
        $fields = [
            'supplier_code', 'model_number', 'description', 'price', 'brand',
            'gross_weight', 'net_weight', 'moq', 'lead_time_days', 'hs_code'
        ];

        foreach ($fields as $field) {
            if (!empty($item->$field) && empty($product->$field)) {
                $updates[$field] = $item->$field;
            }
        }

        if (!empty($updates)) {
            $product->update($updates);
        }
    }

    /**
     * Handle photo upload/copy
     */
    protected function handlePhoto(Product $product, ImportPreviewItem $item): ?string
    {
        // Priority: manual upload > extracted photo > URL
        
        if (!empty($item->photo_path)) {
            // Manual upload
            $product->update(['photo' => $item->photo_path]);
            return null;
        }

        if (!empty($item->photo_temp_path) && $item->photo_extracted) {
            // Copy extracted photo to products directory
            try {
                $extension = pathinfo($item->photo_temp_path, PATHINFO_EXTENSION);
                $newFilename = Str::uuid() . '.' . $extension;
                $newPath = 'products/' . $newFilename;

                Storage::disk('public')->copy($item->photo_temp_path, $newPath);
                $product->update(['photo' => $newPath]);
                
                return null;
            } catch (\Throwable $e) {
                Log::warning('Failed to copy photo', [
                    'product_id' => $product->id,
                    'temp_path' => $item->photo_temp_path,
                    'error' => $e->getMessage(),
                ]);
                return 'Failed to copy photo';
            }
        }

        if (!empty($item->photo_url)) {
            // Download from URL
            try {
                $contents = file_get_contents($item->photo_url);
                $extension = pathinfo(parse_url($item->photo_url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
                $filename = Str::uuid() . '.' . $extension;
                $path = 'products/' . $filename;

                Storage::disk('public')->put($path, $contents);
                $product->update(['photo' => $path]);
                
                return null;
            } catch (\Throwable $e) {
                Log::warning('Failed to download photo from URL', [
                    'product_id' => $product->id,
                    'url' => $item->photo_url,
                    'error' => $e->getMessage(),
                ]);
                return 'Failed to download photo from URL';
            }
        }

        return 'No photo available';
    }

    /**
     * Handle product features
     */
    protected function handleFeatures(Product $product, $features): void
    {
        if (is_string($features)) {
            $features = json_decode($features, true) ?: [];
        }

        if (!is_array($features) || empty($features)) {
            return;
        }

        // Create or attach feature tags
        foreach ($features as $featureName) {
            if (empty($featureName)) {
                continue;
            }

            $tag = Tag::firstOrCreate(
                ['name' => trim($featureName)],
                ['type' => 'feature']
            );

            $product->tags()->syncWithoutDetaching([$tag->id]);
        }
    }

    /**
     * Get or create supplier
     */
    protected function getOrCreateSupplier(): ?Supplier
    {
        if (empty($this->importHistory->supplier_name)) {
            return null;
        }

        return Supplier::firstOrCreate(
            ['name' => $this->importHistory->supplier_name],
            [
                'email' => $this->importHistory->supplier_email,
                'type' => 'manufacturer',
            ]
        );
    }

    /**
     * Generate unique SKU
     */
    protected function generateSKU(): string
    {
        do {
            $sku = 'IMP-' . strtoupper(Str::random(8));
        } while (Product::where('sku', $sku)->exists());

        return $sku;
    }

    /**
     * Generate result message
     */
    protected function generateResultMessage(array $stats): string
    {
        $parts = [];

        if ($stats['success'] > 0) {
            $parts[] = "{$stats['success']} created";
        }

        if ($stats['updated'] > 0) {
            $parts[] = "{$stats['updated']} updated";
        }

        if ($stats['skipped'] > 0) {
            $parts[] = "{$stats['skipped']} skipped";
        }

        if ($stats['errors'] > 0) {
            $parts[] = "{$stats['errors']} errors";
        }

        return 'Import completed: ' . implode(', ', $parts);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ImportSelectedItemsJob failed permanently', [
            'import_id' => $this->importHistory->id,
            'error' => $exception->getMessage(),
        ]);

        $this->importHistory->update([
            'status' => 'failed',
            'result_message' => 'Import failed: ' . $exception->getMessage(),
        ]);
    }
}
