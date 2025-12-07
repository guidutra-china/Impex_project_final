<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class PurchaseOrderExcelService
{
    /**
     * Generate Purchase Order Excel file
     *
     * @param PurchaseOrder $po
     * @return string Path to generated file
     */
    public function generatePO(PurchaseOrder $po): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator('Impex System')
            ->setTitle('Purchase Order - ' . $po->po_number)
            ->setSubject('Purchase Order');

        // Styling
        $headerStyle = [
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']], // Blue
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ];

        $labelStyle = [
            'font' => ['bold' => true, 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E7E6E6']],
        ];

        $tableHeaderStyle = [
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            ],
        ];

        $totalStyle = [
            'font' => ['bold' => true, 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF2CC']], // Light yellow
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            ],
        ];

        $currentRow = 1;

        // Add logo if exists
        $logoPath = public_path('images/logo.svg');
        $logoPngPath = null;
        
        if (file_exists($logoPath)) {
            // Convert SVG to PNG for Excel compatibility
            $logoPngPath = $this->convertSvgToPng($logoPath);
            
            if ($logoPngPath && file_exists($logoPngPath)) {
                $drawing = new Drawing();
                $drawing->setName('Impex Logo');
                $drawing->setDescription('Impex Logo');
                $drawing->setPath($logoPngPath);
                $drawing->setHeight(60);
                $drawing->setCoordinates('A1');
                $drawing->setWorksheet($sheet);
                
                // Adjust row height for logo
                $sheet->getRowDimension(1)->setRowHeight(50);
                $currentRow = 2;
            }
        }

        // Title
        $sheet->setCellValue('A' . $currentRow, 'PURCHASE ORDER');
        $sheet->mergeCells('A' . $currentRow . ':F' . $currentRow);
        $sheet->getStyle('A' . $currentRow)->applyFromArray($headerStyle);
        $sheet->getRowDimension($currentRow)->setRowHeight(35);

        $currentRow += 2;

        // PO Information Section
        $sheet->setCellValue('A' . $currentRow, 'PO Number:');
        $sheet->setCellValue('B' . $currentRow, $po->po_number);
        $sheet->getStyle('A' . $currentRow)->applyFromArray($labelStyle);
        $currentRow++;

        $sheet->setCellValue('A' . $currentRow, 'Revision:');
        $sheet->setCellValue('B' . $currentRow, $po->revision_number);
        $sheet->getStyle('A' . $currentRow)->applyFromArray($labelStyle);
        $currentRow++;

        $sheet->setCellValue('A' . $currentRow, 'PO Date:');
        $sheet->setCellValue('B' . $currentRow, $po->po_date ? $po->po_date->format('M d, Y') : 'N/A');
        $sheet->getStyle('A' . $currentRow)->applyFromArray($labelStyle);
        $currentRow++;

        $sheet->setCellValue('A' . $currentRow, 'Status:');
        $sheet->setCellValue('B' . $currentRow, strtoupper($po->status));
        $sheet->getStyle('A' . $currentRow)->applyFromArray($labelStyle);
        $currentRow++;

        // Proforma Invoice (if linked)
        if ($po->proformaInvoice) {
            $sheet->setCellValue('A' . $currentRow, 'Proforma Invoice:');
            $sheet->setCellValue('B' . $currentRow, $po->proformaInvoice->proforma_number);
            $sheet->getStyle('A' . $currentRow)->applyFromArray($labelStyle);
            $currentRow++;
        }

        $currentRow++;

        // Supplier Information
        $sheet->setCellValue('A' . $currentRow, 'SUPPLIER INFORMATION');
        $sheet->mergeCells('A' . $currentRow . ':F' . $currentRow);
        $sheet->getStyle('A' . $currentRow)->applyFromArray($headerStyle);
        $sheet->getRowDimension($currentRow)->setRowHeight(25);
        $currentRow++;

        $sheet->setCellValue('A' . $currentRow, 'Supplier:');
        $supplierInfo = $po->supplier ? ($po->supplier->supplier_code ? "{$po->supplier->supplier_code} - {$po->supplier->name}" : $po->supplier->name) : 'N/A';
        $sheet->setCellValue('B' . $currentRow, $supplierInfo);
        $sheet->mergeCells('B' . $currentRow . ':F' . $currentRow);
        $sheet->getStyle('A' . $currentRow)->applyFromArray($labelStyle);
        $currentRow++;

        if ($po->supplier && $po->supplier->email) {
            $sheet->setCellValue('A' . $currentRow, 'Email:');
            $sheet->setCellValue('B' . $currentRow, $po->supplier->email);
            $sheet->mergeCells('B' . $currentRow . ':F' . $currentRow);
            $sheet->getStyle('A' . $currentRow)->applyFromArray($labelStyle);
            $currentRow++;
        }

        $currentRow++;

        // Terms & Conditions
        $sheet->setCellValue('A' . $currentRow, 'TERMS & CONDITIONS');
        $sheet->mergeCells('A' . $currentRow . ':F' . $currentRow);
        $sheet->getStyle('A' . $currentRow)->applyFromArray($headerStyle);
        $sheet->getRowDimension($currentRow)->setRowHeight(25);
        $currentRow++;

        $sheet->setCellValue('A' . $currentRow, 'Currency:');
        $sheet->setCellValue('B' . $currentRow, $po->currency ? $po->currency->code : 'N/A');
        $sheet->getStyle('A' . $currentRow)->applyFromArray($labelStyle);
        $currentRow++;

        $sheet->setCellValue('A' . $currentRow, 'Exchange Rate:');
        $sheet->setCellValue('B' . $currentRow, number_format($po->exchange_rate ?? 1, 4));
        $sheet->getStyle('A' . $currentRow)->applyFromArray($labelStyle);
        $currentRow++;

        $sheet->setCellValue('A' . $currentRow, 'Incoterm:');
        $sheet->setCellValue('B' . $currentRow, $po->incoterm ?? 'N/A');
        $sheet->getStyle('A' . $currentRow)->applyFromArray($labelStyle);
        $currentRow++;

        if ($po->incoterm_location) {
            $sheet->setCellValue('A' . $currentRow, 'Incoterm Location:');
            $sheet->setCellValue('B' . $currentRow, $po->incoterm_location);
            $sheet->getStyle('A' . $currentRow)->applyFromArray($labelStyle);
            $currentRow++;
        }

        $sheet->setCellValue('A' . $currentRow, 'Payment Terms:');
        $sheet->setCellValue('B' . $currentRow, $po->paymentTerm ? $po->paymentTerm->name : 'N/A');
        $sheet->getStyle('A' . $currentRow)->applyFromArray($labelStyle);
        $currentRow++;

        if ($po->expected_delivery_date) {
            $sheet->setCellValue('A' . $currentRow, 'Expected Delivery:');
            $sheet->setCellValue('B' . $currentRow, $po->expected_delivery_date->format('M d, Y'));
            $sheet->getStyle('A' . $currentRow)->applyFromArray($labelStyle);
            $currentRow++;
        }

        $currentRow += 2;

        // Order Items Table
        $sheet->setCellValue('A' . $currentRow, 'ORDER ITEMS');
        $sheet->mergeCells('A' . $currentRow . ':F' . $currentRow);
        $sheet->getStyle('A' . $currentRow)->applyFromArray($headerStyle);
        $sheet->getRowDimension($currentRow)->setRowHeight(25);
        $currentRow++;

        // Table headers
        $sheet->setCellValue('A' . $currentRow, '#');
        $sheet->setCellValue('B' . $currentRow, 'Product Code');
        $sheet->setCellValue('C' . $currentRow, 'Product Name');
        $sheet->setCellValue('D' . $currentRow, 'Quantity');
        $sheet->setCellValue('E' . $currentRow, 'Unit Price');
        $sheet->setCellValue('F' . $currentRow, 'Total');
        $sheet->getStyle('A' . $currentRow . ':F' . $currentRow)->applyFromArray($tableHeaderStyle);
        $currentRow++;

        // Items data
        $items = $po->items()->with('product')->get();
        $itemNumber = 1;

        foreach ($items as $item) {
            $sheet->setCellValue('A' . $currentRow, $itemNumber++);
            $sheet->setCellValue('B' . $currentRow, $item->product_sku ?? 'N/A');
            $sheet->setCellValue('C' . $currentRow, $item->product_name ?? $item->product->name ?? 'N/A');
            $sheet->setCellValue('D' . $currentRow, $item->quantity);
            $sheet->setCellValue('E' . $currentRow, number_format($item->unit_cost, 2));
            $sheet->setCellValue('F' . $currentRow, number_format($item->total_cost, 2));
            
            // Borders for table
            $sheet->getStyle('A' . $currentRow . ':F' . $currentRow)->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                ],
            ]);
            
            // Align numbers to right
            $sheet->getStyle('D' . $currentRow . ':F' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            
            $currentRow++;
        }

        // Totals
        $currentRow++;
        
        $sheet->setCellValue('E' . $currentRow, 'Subtotal:');
        $sheet->setCellValue('F' . $currentRow, number_format($po->subtotal ?? 0, 2));
        $sheet->getStyle('E' . $currentRow . ':F' . $currentRow)->applyFromArray($totalStyle);
        $currentRow++;

        if ($po->shipping_cost && $po->shipping_cost > 0) {
            $sheet->setCellValue('E' . $currentRow, 'Shipping:');
            $sheet->setCellValue('F' . $currentRow, number_format($po->shipping_cost, 2));
            $sheet->getStyle('E' . $currentRow . ':F' . $currentRow)->applyFromArray($totalStyle);
            $currentRow++;
        }

        if ($po->tax && $po->tax > 0) {
            $sheet->setCellValue('E' . $currentRow, 'Tax:');
            $sheet->setCellValue('F' . $currentRow, number_format($po->tax, 2));
            $sheet->getStyle('E' . $currentRow . ':F' . $currentRow)->applyFromArray($totalStyle);
            $currentRow++;
        }

        $sheet->setCellValue('E' . $currentRow, 'TOTAL:');
        $sheet->setCellValue('F' . $currentRow, number_format($po->total ?? 0, 2));
        $sheet->getStyle('E' . $currentRow . ':F' . $currentRow)->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']], // Blue
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THICK, 'color' => ['rgb' => '000000']],
            ],
        ]);
        $sheet->getStyle('E' . $currentRow . ':F' . $currentRow)->getFont()->getColor()->setRGB('FFFFFF');

        // Notes
        if ($po->notes) {
            $currentRow += 2;
            $sheet->setCellValue('A' . $currentRow, 'NOTES');
            $sheet->mergeCells('A' . $currentRow . ':F' . $currentRow);
            $sheet->getStyle('A' . $currentRow)->applyFromArray($headerStyle);
            $currentRow++;
            
            $sheet->setCellValue('A' . $currentRow, $po->notes);
            $sheet->mergeCells('A' . $currentRow . ':F' . $currentRow);
            $sheet->getStyle('A' . $currentRow)->getAlignment()->setWrapText(true);
            $sheet->getRowDimension($currentRow)->setRowHeight(40);
        }

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(10);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(40);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(15);

        // Save file
        $filename = 'PO_' . $po->po_number . '_' . now()->format('Ymd_His') . '.xlsx';
        $filepath = storage_path('app/temp/' . $filename);
        
        // Ensure temp directory exists
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);

        // Clean up temporary PNG logo if created
        if ($logoPngPath && file_exists($logoPngPath)) {
            @unlink($logoPngPath);
        }

        // Save to Documents History
        $this->saveToDocumentHistory($po, $filepath, $filename);

        return $filepath;
    }

    /**
     * Convert SVG to PNG for Excel compatibility
     */
    private function convertSvgToPng(string $svgPath): ?string
    {
        try {
            $pngPath = storage_path('app/temp/logo_' . time() . '.png');
            
            // Ensure temp directory exists
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            // Try using Imagick if available
            if (extension_loaded('imagick')) {
                $imagick = new \Imagick();
                $imagick->readImage($svgPath);
                $imagick->setImageFormat('png');
                $imagick->setImageBackgroundColor('white');
                $imagick->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
                $imagick->writeImage($pngPath);
                $imagick->clear();
                $imagick->destroy();
                
                return $pngPath;
            }
            
            // Fallback: return null if Imagick not available
            return null;
        } catch (\Exception $e) {
            \Log::warning('SVG to PNG conversion failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Save generated document to history
     *
     * @param PurchaseOrder $po
     * @param string $filePath
     * @param string $fileName
     * @return void
     */
    protected function saveToDocumentHistory(PurchaseOrder $po, string $filePath, string $fileName): void
    {
        try {
            \Log::info('PO Excel: Starting saveToDocumentHistory', [
                'po_id' => $po->id,
                'temp_file_path' => $filePath,
                'temp_file_exists' => file_exists($filePath),
                'temp_file_size' => file_exists($filePath) ? filesize($filePath) : 0,
            ]);
            
            // Move file from temp to permanent storage using Storage facade
            $directory = "documents/purchase_orders/" . date('Y/m');
            $storagePath = "{$directory}/{$fileName}";
            
            // Read file content and store using Storage facade
            $fileContent = file_get_contents($filePath);
            \Illuminate\Support\Facades\Storage::put($storagePath, $fileContent);
            
            // Verify file was saved
            $exists = \Illuminate\Support\Facades\Storage::exists($storagePath);
            
            if (!$exists) {
                throw new \Exception("Failed to save file to storage: {$storagePath}");
            }
            
            // Create database record using the same pattern as RFQExcelService
            $document = \App\Models\GeneratedDocument::createFromFile(
                $po,
                'purchase_order',
                'excel',
                $storagePath,
                [
                    'document_number' => $po->po_number,
                    'filename' => $fileName,
                ]
            );
            
            \Log::info('PO Excel saved to document history', [
                'po_id' => $po->id,
                'file_path' => $storagePath,
                'document_id' => $document->id,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to save PO Excel to document history', [
                'po_id' => $po->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
