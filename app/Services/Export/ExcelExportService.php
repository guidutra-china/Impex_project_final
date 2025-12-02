<?php

namespace App\Services\Export;

use App\Models\GeneratedDocument;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ExcelExportService
{
    /**
     * Generate Excel for a document
     */
    public function generate(
        Model $model,
        string $documentType,
        array $options = []
    ): GeneratedDocument {
        // Create spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Generate content based on document type
        match ($documentType) {
            'proforma_invoice' => $this->generateProformaInvoice($sheet, $model),
            'commercial_invoice' => $this->generateCommercialInvoice($sheet, $model),
            default => throw new \Exception("Unsupported document type for Excel export: {$documentType}"),
        };
        
        // Generate filename
        $documentNumber = $this->getDocumentNumber($model, $documentType);
        $filename = $this->generateFilename($documentType, $documentNumber, $options);
        
        // Storage path
        $directory = "documents/{$documentType}/" . date('Y/m');
        $filePath = "{$directory}/{$filename}";
        
        // Ensure directory exists
        Storage::makeDirectory($directory);
        
        // Save Excel
        $writer = new Xlsx($spreadsheet);
        $tempPath = storage_path('app/' . $filePath);
        $writer->save($tempPath);
        
        // Create document record
        return GeneratedDocument::createFromFile(
            $model,
            $documentType,
            'excel',
            $filePath,
            [
                'document_number' => $documentNumber,
                'filename' => $filename,
                'revision_number' => $options['revision_number'] ?? null,
                'metadata' => [
                    'format' => 'xlsx',
                ],
                'notes' => $options['notes'] ?? null,
            ]
        );
    }

    /**
     * Generate Proforma Invoice Excel
     */
    protected function generateProformaInvoice($sheet, $model): void
    {
        $row = 1;
        
        // Title
        $sheet->setCellValue('A' . $row, 'PROFORMA INVOICE');
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row += 2;
        
        // Invoice Info
        $sheet->setCellValue('A' . $row, 'Proforma Number:');
        $sheet->setCellValue('B' . $row, $model->proforma_number);
        $sheet->setCellValue('E' . $row, 'Issue Date:');
        $sheet->setCellValue('F' . $row, $model->issue_date->format('Y-m-d'));
        $row++;
        
        $sheet->setCellValue('A' . $row, 'Revision:');
        $sheet->setCellValue('B' . $row, $model->revision_number);
        $sheet->setCellValue('E' . $row, 'Valid Until:');
        $sheet->setCellValue('F' . $row, $model->valid_until->format('Y-m-d'));
        $row++;
        
        $sheet->setCellValue('A' . $row, 'Status:');
        $sheet->setCellValue('B' . $row, strtoupper($model->status));
        $sheet->setCellValue('E' . $row, 'Currency:');
        $sheet->setCellValue('F' . $row, $model->currency->code ?? 'USD');
        $row += 2;
        
        // Customer Info
        $sheet->setCellValue('A' . $row, 'BILL TO:');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        
        $sheet->setCellValue('A' . $row, $model->customer->name);
        $row++;
        if ($model->customer->address) {
            $sheet->setCellValue('A' . $row, $model->customer->address);
            $row++;
        }
        $row++;
        
        // Items Header
        $headers = ['#', 'Code', 'Description', 'Quantity', 'Unit Price', 'Delivery (days)', 'Total'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
            $sheet->getStyle($col . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF1F2937');
            $sheet->getStyle($col . $row)->getFont()->getColor()->setARGB('FFFFFFFF');
            $col++;
        }
        $headerRow = $row;
        $row++;
        
        // Items
        $itemStartRow = $row;
        foreach ($model->items as $index => $item) {
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $item->product->code ?? 'N/A');
            $sheet->setCellValue('C' . $row, $item->product->name ?? $item->product_name);
            $sheet->setCellValue('D' . $row, $item->quantity);
            $sheet->setCellValue('E' . $row, $item->unit_price);
            $sheet->setCellValue('F' . $row, $item->delivery_days);
            $sheet->setCellValue('G' . $row, $item->total);
            
            // Format currency
            $sheet->getStyle('E' . $row)->getNumberFormat()
                ->setFormatCode('#,##0.00');
            $sheet->getStyle('G' . $row)->getNumberFormat()
                ->setFormatCode('#,##0.00');
            
            $row++;
        }
        $itemEndRow = $row - 1;
        
        // Totals
        $row++;
        $sheet->setCellValue('F' . $row, 'Subtotal:');
        $sheet->setCellValue('G' . $row, $model->subtotal);
        $sheet->getStyle('F' . $row)->getFont()->setBold(true);
        $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $row++;
        
        if ($model->tax > 0) {
            $sheet->setCellValue('F' . $row, 'Tax:');
            $sheet->setCellValue('G' . $row, $model->tax);
            $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $row++;
        }
        
        $sheet->setCellValue('F' . $row, 'TOTAL:');
        $sheet->setCellValue('G' . $row, $model->total);
        $sheet->getStyle('F' . $row . ':G' . $row)->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('F' . $row . ':G' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFF3F4F6');
        
        // Borders
        $sheet->getStyle('A' . $headerRow . ':G' . $itemEndRow)->getBorders()
            ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        // Column widths
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(12);
        $sheet->getColumnDimension('C')->setWidth(40);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(15);
    }

    /**
     * Generate Commercial Invoice Excel
     */
    protected function generateCommercialInvoice($sheet, $model): void
    {
        // Similar structure to Proforma Invoice
        // Will be implemented when we adjust Commercial Invoice
        $this->generateProformaInvoice($sheet, $model);
    }

    /**
     * Get document number from model
     */
    protected function getDocumentNumber(Model $model, string $documentType): ?string
    {
        return match ($documentType) {
            'proforma_invoice' => $model->proforma_number ?? null,
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
        
        return "{$prefix}-{$number}{$revision}-{$timestamp}.xlsx";
    }
}
