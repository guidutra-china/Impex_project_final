<?php

namespace App\Jobs;

use App\Models\ImportHistory;
use App\Services\AI\AIFileAnalyzerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AnalyzeImportFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300; // 5 minutes
    public int $tries = 3;

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
            Log::info('Starting AI analysis for import', [
                'import_id' => $this->importHistory->id,
                'file_name' => $this->importHistory->file_name,
            ]);

            // Update status to analyzing
            $this->importHistory->update([
                'status' => 'analyzing',
            ]);

            // Get full file path
            $filePath = Storage::disk('private')->path($this->importHistory->file_path);

            if (!file_exists($filePath)) {
                throw new \Exception('File not found: ' . $this->importHistory->file_path);
            }

            // Analyze file with AI
            $analyzer = new AIFileAnalyzerService();
            $analysis = $analyzer->analyzeFile($filePath);

            // Extract information from AI analysis
            $aiAnalysis = $analysis['ai_analysis'] ?? [];
            $supplierInfo = $analyzer->getSupplierInfo($analysis);
            $documentType = $analyzer->getDocumentType($analysis);
            $productsCount = $analyzer->getProductsCount($analysis);
            $columnMapping = $analyzer->getSuggestedMapping($analysis);

            // Update import history with analysis results
            $this->importHistory->update([
                'status' => 'ready',
                'document_type' => $documentType,
                'supplier_name' => $supplierInfo['name'] ?? null,
                'supplier_email' => $supplierInfo['email'] ?? null,
                'ai_analysis' => $aiAnalysis,
                'column_mapping' => $columnMapping,
                'total_rows' => $productsCount,
                'analyzed_at' => now(),
            ]);

            Log::info('AI analysis completed successfully', [
                'import_id' => $this->importHistory->id,
                'document_type' => $documentType,
                'products_count' => $productsCount,
            ]);

        } catch (\Throwable $e) {
            Log::error('AI analysis failed', [
                'import_id' => $this->importHistory->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->importHistory->update([
                'status' => 'failed',
                'result_message' => 'Analysis failed: ' . $e->getMessage(),
                'errors' => [
                    'analysis_error' => $e->getMessage(),
                ],
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('AnalyzeImportFileJob failed permanently', [
            'import_id' => $this->importHistory->id,
            'error' => $exception->getMessage(),
        ]);

        $this->importHistory->update([
            'status' => 'failed',
            'result_message' => 'Analysis failed after ' . $this->tries . ' attempts: ' . $exception->getMessage(),
        ]);
    }
}
