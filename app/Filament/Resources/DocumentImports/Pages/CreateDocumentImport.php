<?php

namespace App\Filament\Resources\DocumentImports\Pages;

use App\Filament\Resources\DocumentImports\DocumentImportResource;
use App\Models\ImportHistory;
use App\Services\AI\AIFileAnalyzerService;
use App\Services\AI\DynamicProductImporter;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateDocumentImport extends CreateRecord
{
    protected static string $resource = DocumentImportResource::class;

    protected static bool $canCreateAnother = false;

    protected ?array $analysisResult = null;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('import_type')
                    ->label('Import Type')
                    ->options([
                        'products' => 'Products',
                        'suppliers' => 'Suppliers (Coming Soon)',
                        'clients' => 'Clients (Coming Soon)',
                        'quotes' => 'Supplier Quotes (Coming Soon)',
                    ])
                    ->default('products')
                    ->required()
                    ->helperText('Select what type of data you want to import'),

                FileUpload::make('file')
                    ->label('File')
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel',
                        'application/pdf',
                    ])
                    ->maxSize(20480) // 20MB
                    ->required()
                    ->helperText('Upload Excel (.xlsx, .xls) or PDF file. Max size: 20MB. AI will analyze automatically after upload.')
                    ->live()
                    ->afterStateUpdated(function ($state, $set) {
                        if ($state) {
                            $this->analyzeFile($state, $set);
                        }
                    }),

                Placeholder::make('analysis_info')
                    ->label('AI Analysis')
                    ->content(fn ($get) => $this->getAnalysisInfo($get))
                    ->visible(fn () => $this->analysisResult !== null),
            ]);
    }

    protected function analyzeFile($fileState, $set): void
    {
        try {
            if (!$fileState) {
                return;
            }

            // Get the actual file path
            $filePath = storage_path('app/' . $fileState);

            if (!file_exists($filePath)) {
                Notification::make()
                    ->danger()
                    ->title('File not found')
                    ->body('Uploaded file could not be found')
                    ->send();
                return;
            }

            // Analyze with AI
            $analyzer = new AIFileAnalyzerService();
            $this->analysisResult = $analyzer->analyzeFile($filePath);

            Notification::make()
                ->success()
                ->title('Analysis Complete')
                ->body('File analyzed successfully by AI. Review the results below.')
                ->send();

        } catch (\Throwable $e) {
            Notification::make()
                ->danger()
                ->title('Analysis Failed')
                ->body($e->getMessage())
                ->send();

            \Log::error('File analysis failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    protected function getAnalysisInfo($get): string
    {
        if (!$this->analysisResult) {
            return '⏳ Waiting for file upload...';
        }

        $aiAnalysis = $this->analysisResult['ai_analysis'] ?? [];
        
        if (empty($aiAnalysis)) {
            return '⚠️ Analysis completed but no AI insights available';
        }

        $documentType = $aiAnalysis['document_type'] ?? 'Unknown';
        $productsCount = $aiAnalysis['products_count'] ?? 0;
        $supplierName = $aiAnalysis['supplier']['name'] ?? 'Unknown';
        $hasImages = $this->analysisResult['has_images'] ?? false;
        $imageCount = count($this->analysisResult['images'] ?? []);
        $currency = $aiAnalysis['currency'] ?? 'USD';

        $info = "✅ **Analysis Complete!**\n\n";
        $info .= "📄 **Document Type:** {$documentType}\n";
        $info .= "📦 **Products Found:** {$productsCount}\n";
        $info .= "🏭 **Supplier:** {$supplierName}\n";
        $info .= "💰 **Currency:** {$currency}\n";
        $info .= "📷 **Images:** " . ($hasImages ? "{$imageCount} found" : "None") . "\n\n";
        $info .= "Click **Create** below to start the import process.";

        return $info;
    }

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
            $data['file_type'] = $this->analysisResult['file_type'] ?? 'unknown';
        }

        // Add AI analysis data
        if ($this->analysisResult) {
            $aiAnalysis = $this->analysisResult['ai_analysis'] ?? [];
            
            $data['ai_analysis'] = $aiAnalysis;
            $data['document_type'] = $aiAnalysis['document_type'] ?? null;
            $data['total_rows'] = $aiAnalysis['products_count'] ?? count($this->analysisResult['all_rows'] ?? []);
            
            if (isset($aiAnalysis['supplier'])) {
                $data['supplier_name'] = $aiAnalysis['supplier']['name'] ?? null;
                $data['supplier_email'] = $aiAnalysis['supplier']['email'] ?? null;
            }

            if (isset($aiAnalysis['column_mapping'])) {
                $data['column_mapping'] = $aiAnalysis['column_mapping'];
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
