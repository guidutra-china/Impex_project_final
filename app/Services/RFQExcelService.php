<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Supplier;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class RFQExcelService
{
    /**
     * Generate RFQ Excel file
     *
     * @param Order $order
     * @param \App\Models\Supplier|null $supplier If provided, generates RFQ only with products matching this supplier's tags
     * @return string Path to generated file
     */
    public function generateRFQ(Order $order, $supplier = null): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator('Impex System')
            ->setTitle('RFQ - ' . $order->order_number)
            ->setSubject('Request for Quotation');

        // Header styling
        $headerStyle = [
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ];

        $labelStyle = [
            'font' => ['bold' => true, 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E7E6E6']],
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
        $sheet->setCellValue('A' . $currentRow, 'REQUEST FOR QUOTATION');
        $sheet->mergeCells('A' . $currentRow . ':E' . $currentRow);
        $sheet->getStyle('A' . $currentRow)->applyFromArray($headerStyle);
        $sheet->getRowDimension($currentRow)->setRowHeight(30);

        $currentRow += 2;

        // RFQ Number
        $sheet->setCellValue('A' . $currentRow, 'RFQ Number:');
        $sheet->setCellValue('B' . $currentRow, $order->order_number);
        $sheet->getStyle('A' . $currentRow)->applyFromArray($labelStyle);
        $currentRow++;

        // Order Currency
        $sheet->setCellValue('A' . $currentRow, 'Order Currency:');
        $sheet->setCellValue('B' . $currentRow, $order->currency ? $order->currency->code : 'N/A');
        $sheet->getStyle('A' . $currentRow)->applyFromArray($labelStyle);
        $currentRow++;

        // Supplier (if provided)
        if ($supplier) {
            $sheet->setCellValue('A' . $currentRow, 'Supplier:');
            $supplierInfo = $supplier->supplier_code ? "{$supplier->supplier_code} - {$supplier->name}" : $supplier->name;
            $sheet->setCellValue('B' . $currentRow, $supplierInfo);
            $sheet->mergeCells('B' . $currentRow . ':E' . $currentRow);
            $sheet->getStyle('A' . $currentRow)->applyFromArray($labelStyle);
            $currentRow++;
        }

        // Customer Request
        $sheet->setCellValue('A' . $currentRow, 'Customer Request:');
        $sheet->getStyle('A' . $currentRow)->applyFromArray($labelStyle);
        $currentRow++;
        
        $sheet->setCellValue('A' . $currentRow, $order->customer_notes ?? 'No customer request');
        $sheet->mergeCells('A' . $currentRow . ':E' . $currentRow);
        $sheet->getStyle('A' . $currentRow)->getAlignment()->setWrapText(true);
        $sheet->getRowDimension($currentRow)->setRowHeight(60);
        $currentRow += 2;

        // Order Items
        // If supplier is provided, filter items to only include products matching supplier's tags
        if ($supplier) {
            $matchingService = new RFQMatchingService();
            $items = $matchingService->getOrderItemsForSupplier($order, $supplier);
            $items->load(['product', 'product.features']);
        } else {
            $items = $order->items()->with(['product', 'product.features'])->get();
        }

        // Always add ORDER ITEMS section
        // Items header
        $sheet->setCellValue('A' . $currentRow, $items->isNotEmpty() ? 'ORDER ITEMS' : 'ORDER ITEMS (To be filled by supplier)');
        $sheet->mergeCells('A' . $currentRow . ':E' . $currentRow);
        $sheet->getStyle('A' . $currentRow)->applyFromArray($headerStyle);
        $currentRow++;

        // Table headers
        $sheet->setCellValue('A' . $currentRow, 'Product Name');
        $sheet->setCellValue('B' . $currentRow, 'Quantity');
        
        if ($items->isNotEmpty()) {
            // When items exist: Target Price | Supplier Price | Features
            $sheet->setCellValue('C' . $currentRow, 'Target Price');
            $sheet->setCellValue('D' . $currentRow, 'Supplier Price');
            $sheet->setCellValue('E' . $currentRow, 'Features');
            $sheet->getStyle('A' . $currentRow . ':E' . $currentRow)->applyFromArray($labelStyle);
        } else {
            // When no items: Unit Price | Description / Features (no Supplier Price column)
            $sheet->setCellValue('C' . $currentRow, 'Unit Price');
            $sheet->setCellValue('D' . $currentRow, 'Description / Features');
            $sheet->getStyle('A' . $currentRow . ':D' . $currentRow)->applyFromArray($labelStyle);
        }
        $currentRow++;

        if ($items->isNotEmpty()) {
            // Items data
            foreach ($items as $item) {
                $startRow = $currentRow;
                
                // Product name
                $sheet->setCellValue('A' . $currentRow, $item->product->name ?? 'N/A');
                
                // Quantity
                $sheet->setCellValue('B' . $currentRow, $item->quantity);
                
                // Target price
                $targetPrice = $item->requested_unit_price 
                    ? number_format($item->requested_unit_price, 2)
                    : 'N/A';
                $sheet->setCellValue('C' . $currentRow, $targetPrice);
                
                // Supplier Price (empty for supplier to fill)
                $sheet->setCellValue('D' . $currentRow, '');
                $sheet->getStyle('D' . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFCC'); // Light yellow
                
                // Features
                $features = $item->product->features ?? collect();
                if ($features->isNotEmpty()) {
                    $featuresList = $features->map(function ($feature) {
                        $unit = $feature->unit ? " {$feature->unit}" : '';
                        return "• {$feature->feature_name}: {$feature->feature_value}{$unit}";
                    })->implode("\n");
                    $sheet->setCellValue('E' . $currentRow, $featuresList);
                    $sheet->getStyle('E' . $currentRow)->getAlignment()->setWrapText(true);
                    
                    // Adjust row height based on number of features
                    $rowHeight = max(30, $features->count() * 15);
                    $sheet->getRowDimension($currentRow)->setRowHeight($rowHeight);
                } else {
                    $sheet->setCellValue('E' . $currentRow, 'No features');
                }

                // Apply borders
                $sheet->getStyle('A' . $currentRow . ':E' . $currentRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                    ],
                ]);

                $currentRow++;
            }
        } else {
            // No items - add empty rows for supplier to fill (only 4 columns: A-D)
            $emptyRowsCount = 15; // Number of empty rows to add
            
            for ($i = 0; $i < $emptyRowsCount; $i++) {
                // All cells are empty and editable
                $sheet->setCellValue('A' . $currentRow, ''); // Product Name
                $sheet->setCellValue('B' . $currentRow, ''); // Quantity
                $sheet->setCellValue('C' . $currentRow, ''); // Unit Price
                $sheet->setCellValue('D' . $currentRow, ''); // Description / Features
                
                // Highlight all cells in yellow to indicate they should be filled
                $sheet->getStyle('A' . $currentRow . ':D' . $currentRow)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('FFFFCC'); // Light yellow
                
                // Apply borders
                $sheet->getStyle('A' . $currentRow . ':D' . $currentRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                    ],
                ]);
                
                // Set row height
                $sheet->getRowDimension($currentRow)->setRowHeight(25);
                
                $currentRow++;
            }
        }

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(40);

        // Generate file
        $supplierSuffix = $supplier ? '_' . str_replace(' ', '-', $supplier->name) : '';
        $fileName = 'RFQ_' . $order->order_number . $supplierSuffix . '_' . time() . '.xlsx';
        $filePath = storage_path('app/temp/' . $fileName);

        // Ensure temp directory exists
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        // Clean up temporary PNG logo if created
        if ($logoPngPath && file_exists($logoPngPath)) {
            unlink($logoPngPath);
        }

        // Save to Documents History
        $this->saveToDocumentHistory($order, $filePath, $fileName);

        return $filePath;
    }

    /**
     * Save generated document to history
     *
     * @param Order $order
     * @param string $filePath
     * @param string $fileName
     * @return void
     */
    protected function saveToDocumentHistory(Order $order, string $filePath, string $fileName): void
    {
        \Log::info('RFQ: saveToDocumentHistory() METHOD CALLED', [
            'order_id' => $order->id,
            'file_path' => $filePath,
            'filename' => $fileName,
        ]);
        
        try {
            \Log::info('RFQ: Starting saveToDocumentHistory', [
                'order_id' => $order->id,
                'temp_file_path' => $filePath,
                'temp_file_exists' => file_exists($filePath),
                'temp_file_size' => file_exists($filePath) ? filesize($filePath) : 0,
            ]);
            
            // Move file from temp to permanent storage using Storage facade
            $directory = "documents/rfq/" . date('Y/m');
            $storagePath = "{$directory}/{$fileName}";
            
            \Log::info('RFQ: Calculated storage path', [
                'directory' => $directory,
                'storage_path' => $storagePath,
                'filename' => $fileName,
            ]);
            
            // Read file content and store using Storage facade
            $fileContent = file_get_contents($filePath);
            \Log::info('RFQ: File content read', [
                'content_size' => strlen($fileContent),
            ]);
            
            \Illuminate\Support\Facades\Storage::put($storagePath, $fileContent);
            \Log::info('RFQ: Storage::put() called', [
                'storage_path' => $storagePath,
            ]);
            
            // Verify file was saved
            $exists = \Illuminate\Support\Facades\Storage::exists($storagePath);
            \Log::info('RFQ: Checking if file exists', [
                'storage_path' => $storagePath,
                'exists' => $exists,
                'full_path' => storage_path('app/' . $storagePath),
                'full_path_exists' => file_exists(storage_path('app/' . $storagePath)),
            ]);
            
            if (!$exists) {
                throw new \Exception("Failed to save file to storage: {$storagePath}");
            }
            
            // Create database record using the same pattern as PdfExportService
            $document = \App\Models\GeneratedDocument::createFromFile(
                $order,
                'rfq',
                'excel',
                $storagePath,
                [
                    'document_number' => $order->order_number,
                    'filename' => $fileName,
                ]
            );
            
            \Log::info('RFQ Excel saved to document history', [
                'order_id' => $order->id,
                'file_path' => $storagePath,
                'document_id' => $document->id,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to save RFQ to document history', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Convert SVG to PNG for Excel compatibility
     *
     * @param string $svgPath
     * @return string|null Path to PNG file or null if conversion fails
     */
    protected function convertSvgToPng(string $svgPath): ?string
    {
        try {
            // For SVG, we'll create a simple placeholder or use ImageMagick if available
            // Check if Imagick extension is available
            if (extension_loaded('imagick')) {
                $imagick = new \Imagick();
                $imagick->setBackgroundColor(new \ImagickPixel('transparent'));
                $imagick->readImage($svgPath);
                $imagick->setImageFormat('png');
                
                $pngPath = storage_path('app/temp/logo_' . time() . '.png');
                $imagick->writeImage($pngPath);
                $imagick->clear();
                $imagick->destroy();
                
                return $pngPath;
            }
            
            // If no Imagick, try to find a PNG logo instead
            $pngLogoPath = public_path('images/logo.png');
            if (file_exists($pngLogoPath)) {
                return $pngLogoPath;
            }
            
            return null;
        } catch (\Exception $e) {
            \Log::warning('Failed to convert SVG to PNG: ' . $e->getMessage());
            
            // Try to find PNG alternative
            $pngLogoPath = public_path('images/logo.png');
            if (file_exists($pngLogoPath)) {
                return $pngLogoPath;
            }
            
            return null;
        }
    }
}
