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
            Storage::put($filePath, $pdfContent);
            
            // Verify file was created
            if (!Storage::exists($filePath)) {
                throw new \Exception("PDF file was not created at: {$filePath}");
            }
        } catch (\Exception $e) {
            \Log::error('PDF generation failed', [
                'error' => $e->getMessage(),
                'file_path' => $filePath,
                'full_path' => $fullPath,
            ]);
            throw $e;
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
            'commercial_invoice' => $model->invoice_number ?? null,
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
