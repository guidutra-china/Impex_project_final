<?php

namespace App\Jobs;

use App\Models\ImportHistory;
use App\Services\AI\AIFileAnalyzerService;
use App\Services\AI\DynamicProductImporter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessProductImportJob implements ShouldQueue
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
            Log::info('Starting product import', [
                'import_id' => $this->importHistory->id,
                'file_name' => $this->importHistory->file_name,
            ]);

            // Update status to importing
            $this->importHistory->update([
                'status' => 'importing',
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
            $mapping = $this->importHistory->column_mapping ?? $analyzer->getSuggestedMapping($analysis);

            if (empty($mapping)) {
                throw new \Exception('No column mapping available. Please analyze the file first.');
            }

            // Prepare import options
            $options = [
                'supplier' => [
                    'name' => $this->importHistory->supplier_name,
                    'email' => $this->importHistory->supplier_email,
                ],
                'tags' => $analyzer->getSuggestedTags($analysis),
                'currency' => $analyzer->getCurrency($analysis),
            ];

            // Execute import
            $importer = new DynamicProductImporter();
            $result = $importer->import($analysis, $mapping, $options);

            // Update import history with results
            $this->importHistory->update([
                'status' => $result['success'] ? 'completed' : 'failed',
                'success_count' => $result['stats']['success'] ?? 0,
                'updated_count' => $result['stats']['updated'] ?? 0,
                'skipped_count' => $result['stats']['skipped'] ?? 0,
                'error_count' => $result['stats']['errors'] ?? 0,
                'warning_count' => $result['stats']['warnings'] ?? 0,
                'errors' => $result['errors'] ?? [],
                'warnings' => $result['warnings'] ?? [],
                'result_message' => $result['message'] ?? 'Import completed',
                'imported_at' => now(),
            ]);

            Log::info('Product import completed', [
                'import_id' => $this->importHistory->id,
                'success' => $result['success'],
                'stats' => $result['stats'],
            ]);

        } catch (\Throwable $e) {
            Log::error('Product import failed', [
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
                'error_count' => ($this->importHistory->error_count ?? 0) + 1,
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessProductImportJob failed permanently', [
            'import_id' => $this->importHistory->id,
            'error' => $exception->getMessage(),
        ]);

        $this->importHistory->update([
            'status' => 'failed',
            'result_message' => 'Import failed after ' . $this->tries . ' attempts: ' . $exception->getMessage(),
        ]);
    }
}
