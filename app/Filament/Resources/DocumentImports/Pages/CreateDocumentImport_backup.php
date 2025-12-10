<?php

namespace App\Filament\Resources\DocumentImports\Pages;

use App\Filament\Resources\DocumentImports\DocumentImportResource;
use App\Models\ImportHistory;
use App\Services\AI\AIFileAnalyzerService;
use App\Services\AI\DynamicProductImporter;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateDocumentImport_backup extends CreateRecord
{
    protected static string $resource = DocumentImportResource::class;

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Add user ID
        $data['user_id'] = auth()->id();

        // Add file information
        if (isset($data['file'])) {
            $filePath = storage_path('app/' . $data['file']);
            $data['file_name'] = basename($filePath);
            $data['file_path'] = $data['file'];
            $data['file_size'] = file_exists($filePath) ? filesize($filePath) : 0;
            
            // Analyze file with AI
            try {
                $analyzer = new AIFileAnalyzerService();
                $analysisResult = $analyzer->analyzeFile($filePath);
                
                $data['file_type'] = $analysisResult['file_type'] ?? 'unknown';
                
                // Add AI analysis data
                if ($analysisResult) {
                    $aiAnalysis = $analysisResult['ai_analysis'] ?? [];
                    
                    $data['ai_analysis'] = $aiAnalysis;
                    $data['document_type'] = $aiAnalysis['document_type'] ?? null;
                    $data['total_rows'] = $aiAnalysis['products_count'] ?? count($analysisResult['all_rows'] ?? []);
                    
                    if (isset($aiAnalysis['supplier'])) {
                        $data['supplier_name'] = $aiAnalysis['supplier']['name'] ?? null;
                        $data['supplier_email'] = $aiAnalysis['supplier']['email'] ?? null;
                    }

                    if (isset($aiAnalysis['column_mapping'])) {
                        $data['column_mapping'] = $aiAnalysis['column_mapping'];
                    }
                }
                
                Notification::make()
                    ->success()
                    ->title('File Analyzed')
                    ->body('AI analysis completed successfully')
                    ->send();
                    
            } catch (\Throwable $e) {
                \Log::error('File analysis failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                Notification::make()
                    ->warning()
                    ->title('Analysis Failed')
                    ->body('Could not analyze file with AI: ' . $e->getMessage())
                    ->send();
            }
        }

        $data['status'] = 'ready';
        $data['analyzed_at'] = now();

        return $data;
    }

    protected function afterCreate(): void
    {
        // Immediately start import process
        $this->executeImport($this->record);
    }

    protected function executeImport(ImportHistory $import): void
    {
        try {
            $import->update(['status' => 'importing']);

            // Get file path
            $filePath = storage_path('app/' . $import->file_path);

            // Re-analyze file (we need the full data)
            $analyzer = new AIFileAnalyzerService();
            $analysis = $analyzer->analyzeFile($filePath);

            // Get mapping
            $mapping = $import->column_mapping ?? [];

            // Execute import
            $importer = new DynamicProductImporter();
            $result = $importer->import($analysis, $mapping, [
                'supplier' => [
                    'name' => $import->supplier_name,
                    'email' => $import->supplier_email,
                ],
                'tags' => $analysis['ai_analysis']['suggested_tags'] ?? [],
                'currency' => $analysis['ai_analysis']['currency'] ?? 'USD',
            ]);

            // Update import record with results
            $import->update([
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

            Notification::make()
                ->success()
                ->title('Import Completed')
                ->body($result['message'])
                ->send();

        } catch (\Throwable $e) {
            $import->update([
                'status' => 'failed',
                'errors' => [$e->getMessage()],
                'result_message' => 'Import failed: ' . $e->getMessage(),
            ]);

            Notification::make()
                ->danger()
                ->title('Import Failed')
                ->body($e->getMessage())
                ->send();

            \Log::error('Import execution failed', [
                'import_id' => $import->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return DocumentImportResource::getUrl('view', ['record' => $this->record]);
    }
}
