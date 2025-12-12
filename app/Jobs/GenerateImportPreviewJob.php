<?php

namespace App\Jobs;

use App\Models\ImportHistory;
use App\Models\ImportPreviewItem;
use App\Services\AI\AIFileAnalyzerService;
use App\Services\AI\FieldMappingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateImportPreviewJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600; // 10 minutes
    public int $tries = 2;

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
            Log::info('Starting import preview generation', [
                'import_id' => $this->importHistory->id,
                'file_name' => $this->importHistory->file_name,
            ]);

            // Update status
            $this->importHistory->update([
                'status' => 'generating_preview',
            ]);

            // Get full file path
            $filePath = Storage::disk('private')->path($this->importHistory->file_path);

            if (!file_exists($filePath)) {
                throw new \Exception('File not found: ' . $this->importHistory->file_path);
            }

            // Re-parse file to get all data
            $analyzer = new AIFileAnalyzerService();
            $analysis = $analyzer->analyzeFile($filePath);

            // Get mapping (use stored mapping if available, otherwise use AI-suggested)
            $mapping = $this->importHistory->column_mapping ?? [];

            if (empty($mapping)) {
                throw new \Exception('No column mapping available. Please configure mapping first.');
            }

            // Get all rows from analysis
            $allRows = $analysis['all_rows'] ?? [];
            $images = $analysis['images'] ?? [];

            if (empty($allRows)) {
                throw new \Exception('No data rows found in file');
            }

            // Clear existing preview items
            $this->importHistory->previewItems()->delete();

            $successCount = 0;
            $errorCount = 0;

            // Generate preview items
            foreach ($allRows as $rowData) {
                try {
                    $previewItem = $this->createPreviewItem($rowData, $mapping, $images);
                    
                    if ($previewItem) {
                        // Detect duplicates
                        $previewItem->detectDuplicate();
                        
                        // Validate data
                        $previewItem->validate();
                        
                        $successCount++;
                    }
                } catch (\Throwable $e) {
                    Log::warning('Failed to create preview item', [
                        'import_id' => $this->importHistory->id,
                        'row' => $rowData['_row_number'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                    $errorCount++;
                }
            }

            // Update import history
            $this->importHistory->update([
                'status' => 'preview_ready',
                'total_rows' => $successCount,
                'error_count' => $errorCount,
                'result_message' => "Preview generated: {$successCount} items ready for review",
            ]);

            Log::info('Import preview generated successfully', [
                'import_id' => $this->importHistory->id,
                'success_count' => $successCount,
                'error_count' => $errorCount,
            ]);

        } catch (\Throwable $e) {
            Log::error('Import preview generation failed', [
                'import_id' => $this->importHistory->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->importHistory->update([
                'status' => 'failed',
                'result_message' => 'Preview generation failed: ' . $e->getMessage(),
                'errors' => [
                    'preview_error' => $e->getMessage(),
                ],
            ]);

            throw $e;
        }
    }

    /**
     * Create preview item from row data
     */
    protected function createPreviewItem(array $rowData, array $mapping, array $images): ?ImportPreviewItem
    {
        $rowNumber = $rowData['_row_number'] ?? 0;
        
        // Apply field mapping
        $mappedData = FieldMappingService::applyMapping($rowData, $mapping);

        // Check if row has minimum required data
        if (empty($mappedData['name'])) {
            return null;
        }

        // Check for photo in this row
        $photoInfo = $this->findPhotoForRow($rowNumber, $rowData, $images, $mapping);

        // Create preview item
        $previewItem = ImportPreviewItem::create([
            'import_history_id' => $this->importHistory->id,
            'row_number' => $rowNumber,
            'raw_data' => $rowData,
            'data' => $mappedData, // Save all mapped data in JSON field
            'photo_temp_path' => $photoInfo['path'] ?? null,
            'photo_url' => $mappedData['photo_url'] ?? null,
            'photo_status' => $photoInfo['status'] ?? 'none',
            'photo_extracted' => $photoInfo['extracted'] ?? false,
            'photo_error' => $photoInfo['error'] ?? null,
        ]);

        return $previewItem;
    }

    /**
     * Find photo for specific row
     */
    protected function findPhotoForRow(int $rowNumber, array $rowData, array $images, array $mapping): array
    {
        // Find photo column from mapping
        $photoColumn = null;
        foreach ($mapping as $column => $config) {
            if (($config['field'] ?? null) === 'photo_url') {
                $photoColumn = $column;
                break;
            }
        }

        // Check if there's an image in the photo column for this row
        if ($photoColumn) {
            $cellCoordinate = $photoColumn . $rowNumber;
            
            if (isset($images[$cellCoordinate])) {
                return [
                    'path' => $images[$cellCoordinate],
                    'status' => 'extracted',
                    'extracted' => true,
                ];
            }
        }

        // Check if there's a URL in the data
        if (!empty($rowData[$photoColumn] ?? null)) {
            $url = $rowData[$photoColumn];
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                return [
                    'path' => null,
                    'status' => 'none', // URL will be handled during import
                    'extracted' => false,
                ];
            }
        }

        // No photo found
        return [
            'path' => null,
            'status' => 'missing',
            'extracted' => false,
        ];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateImportPreviewJob failed permanently', [
            'import_id' => $this->importHistory->id,
            'error' => $exception->getMessage(),
        ]);

        $this->importHistory->update([
            'status' => 'failed',
            'result_message' => 'Preview generation failed after ' . $this->tries . ' attempts: ' . $exception->getMessage(),
        ]);
    }
}
