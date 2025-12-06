<?php

namespace App\Services\Export;

use App\Models\GeneratedDocument;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfExportService
{
    /**
     * Generate PDF for a document
     */
    public function generate(
        Model $model,
        string $documentType,
        string $view,
        array $data = [],
        array $options = []
    ): GeneratedDocument {
        // Prepare data
        $data['model'] = $model;
        $data['generated_at'] = now();
        
        // Generate PDF
        $pdf = Pdf::loadView($view, $data);
        
        // Set paper size and orientation
        $pdf->setPaper($options['paper'] ?? 'a4', $options['orientation'] ?? 'portrait');
        
        // Generate filename
        $documentNumber = $this->getDocumentNumber($model, $documentType);
        $filename = $this->generateFilename($documentType, $documentNumber, $options);
        
        // Storage path
        $directory = "documents/{$documentType}/" . date('Y/m');
        $filePath = "{$directory}/{$filename}";
        
        // Ensure directory exists
        Storage::makeDirectory($directory);
        
        // Also ensure the full path exists using mkdir
        $fullPath = storage_path('app/' . $filePath);
        $dirPath = dirname($fullPath);
        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0755, true);
        }
        
        // Save PDF
        try {
            $pdfContent = $pdf->output();
            
            if (empty($pdfContent)) {
                throw new \Exception("PDF content is empty - template rendering may have failed");
            }
            
            \Log::info('PDF content generated', [
                'content_length' => strlen($pdfContent),
                'file_path' => $filePath,
                'full_path' => $fullPath,
            ]);
            
            // Ensure directory exists
            $directory = dirname($fullPath);
            if (!is_dir($directory)) {
                \Log::info('Creating directory', ['directory' => $directory]);
                mkdir($directory, 0755, true);
            }
            
            // Try Storage::put first
            $putResult = Storage::put($filePath, $pdfContent);
            
            \Log::info('Storage::put result', [
                'result' => $putResult,
                'exists_after_put' => Storage::exists($filePath),
            ]);
            
            // If Storage::put failed, try direct file_put_contents
            if (!file_exists($fullPath)) {
                \Log::warning('Storage::put did not create file, trying file_put_contents');
                $bytes = file_put_contents($fullPath, $pdfContent);
                \Log::info('file_put_contents result', ['bytes_written' => $bytes]);
            }
            
            // Verify file was created and has content
            if (!Storage::exists($filePath)) {
                throw new \Exception("PDF file was not created at: {$filePath}");
            }
            
            if (!file_exists($fullPath)) {
                throw new \Exception("PDF file does not exist at full path: {$fullPath}");
            }
            
            $fileSize = filesize($fullPath);
            if ($fileSize === 0) {
                throw new \Exception("PDF file was created but is empty (0 bytes)");
            }
            
            \Log::info('PDF generated successfully', [
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'document_type' => $documentType,
            ]);
            
        } catch (\Exception $e) {
            \Log::error('PDF generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file_path' => $filePath,
                'full_path' => $fullPath,
                'document_type' => $documentType,
                'view' => $view,
            ]);
            
            // Clean up partial file if it exists
            if (Storage::exists($filePath)) {
                Storage::delete($filePath);
            }
            
            throw new \Exception("PDF generation failed: " . $e->getMessage(), 0, $e);
        }
        
        // Create document record
        return GeneratedDocument::createFromFile(
            $model,
            $documentType,
            'pdf',
            $filePath,
            [
                'document_number' => $documentNumber,
                'filename' => $filename,
                'revision_number' => $options['revision_number'] ?? null,
                'metadata' => [
                    'view' => $view,
                    'paper' => $options['paper'] ?? 'a4',
                    'orientation' => $options['orientation'] ?? 'portrait',
                ],
                'notes' => $options['notes'] ?? null,
            ]
        );
    }

    /**
     * Get document number from model
     */
    protected function getDocumentNumber(Model $model, string $documentType): ?string
    {
        return match ($documentType) {
            'rfq' => $model->order_number ?? null,
            'supplier_quote' => $model->quote_number ?? null,
            'proforma_invoice' => $model->proforma_number ?? null,
            'purchase_order' => $model->po_number ?? null,
            'commercial_invoice' => $model->commercialInvoice?->invoice_number ?? "CI-{$model->shipment_number}",
            'commercial_invoice_customs' => $model->commercialInvoice?->invoice_number ?? "CI-{$model->shipment_number}",
            default => null,
        };
    }

    /**
     * Generate filename
     */
    protected function generateFilename(string $documentType, ?string $documentNumber, array $options): string
    {
        $prefix = strtoupper(str_replace('_', '-', $documentType));
        $number = $documentNumber ?? date('YmdHis');
        $revision = isset($options['revision_number']) ? "-R{$options['revision_number']}" : '';
        $timestamp = date('Ymd-His');
        
        return "{$prefix}-{$number}{$revision}-{$timestamp}.pdf";
    }
}
