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

        // QUOTATION DETAILS Section (To be filled by Supplier)
        $sheet->setCellValue('A' . $currentRow, 'QUOTATION DETAILS (To be filled by Supplier)');
        $sheet->mergeCells('A' . $currentRow . ':E' . $currentRow);
        $sheet->getStyle('A' . $currentRow)->applyFromArray($headerStyle);
        $sheet->getRowDimension($currentRow)->setRowHeight(25);
        $currentRow++;

        // MOQ
        $sheet->setCellValue('A' . $currentRow, 'MOQ (Minimum Order Quantity):');
        $sheet->setCellValue('B' . $currentRow, '');
        $sheet->mergeCells('B' . $currentRow . ':E' . $currentRow);
        $sheet->getStyle('A' . $currentRow)->applyFromArray($labelStyle);
        $sheet->getStyle('B' . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFCC'); // Yellow
        $currentRow++;

        // Lead Time
        $sheet->setCellValue('A' . $currentRow, 'Lead Time (days):');
        $sheet->setCellValue('B' . $currentRow, '');
        $sheet->mergeCells('B' . $currentRow . ':E' . $currentRow);
        $sheet->getStyle('A' . $currentRow)->applyFromArray($labelStyle);
        $sheet->getStyle('B' . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFCC');
        $currentRow++;

        // Incoterm
        $sheet->setCellValue('A' . $currentRow, 'Incoterm (FOB/CIF/EXW/DDP/etc):');
        $sheet->setCellValue('B' . $currentRow, '');
        $sheet->mergeCells('B' . $currentRow . ':E' . $currentRow);
        $sheet->getStyle('A' . $currentRow)->applyFromArray($labelStyle);
        $sheet->getStyle('B' . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFCC');
        $currentRow++;

        // Payment Terms
        $sheet->setCellValue('A' . $currentRow, 'Payment Terms:');
        $sheet->setCellValue('B' . $currentRow, '');
        $sheet->mergeCells('B' . $currentRow . ':E' . $currentRow);
        $sheet->getStyle('A' . $currentRow)->applyFromArray($labelStyle);
        $sheet->getStyle('B' . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFCC');
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
        $sheet->setCellValue('A' . $currentRow, 'Product Description');
        $sheet->setCellValue('B' . $currentRow, 'Quantity');
        
        if ($items->isNotEmpty()) {
            // When items exist: Target Price | Supplier Price | Total Target
            $sheet->setCellValue('C' . $currentRow, 'Target Price');
            $sheet->setCellValue('D' . $currentRow, 'Supplier Price');
            $sheet->setCellValue('E' . $currentRow, 'Total Target');
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
                
                // Product description (name + code + customer description + features)
                $productDescription = $item->product->name ?? 'N/A';
                
                // Add product code
                if ($item->product->code) {
                    $productDescription .= "\nCode: " . $item->product->code;
                }
                
                // Add customer description
                if ($item->product->customer_description) {
                    $productDescription .= "\n" . $item->product->customer_description;
                }
                
                // Add features
                $features = $item->product->features ?? collect();
                if ($features->isNotEmpty()) {
                    $featuresList = $features->map(function ($feature) {
                        return "{$feature->feature_name}: {$feature->feature_value}";
                    })->implode(', ');
                    $productDescription .= "\nFeatures: " . $featuresList;
                }
                
                // Add item notes
                if ($item->notes) {
                    $productDescription .= "\nNote: " . $item->notes;
                }
                
                $sheet->setCellValue('A' . $currentRow, $productDescription);
                $sheet->getStyle('A' . $currentRow)->getAlignment()->setWrapText(true);
                
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
                
                // Total Target (calculated)
                if ($item->requested_unit_price) {
                    $totalValue = $item->requested_unit_price * $item->quantity;
                    $sheet->setCellValue('E' . $currentRow, $totalValue);
                    $sheet->getStyle('E' . $currentRow)->getNumberFormat()->setFormatCode('#,##0.00');
                } else {
                    $sheet->setCellValue('E' . $currentRow, 'N/A');
                }
                
                // Adjust row height for wrapped text
                $sheet->getRowDimension($currentRow)->setRowHeight(60);

                // Apply borders
                $sheet->getStyle('A' . $currentRow . ':E' . $currentRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                    ],
                ]);

                $currentRow++;
            }
            
            // Add Total Target Value row
            $currentRow++;
            $sheet->setCellValue('D' . $currentRow, 'Total Target Value:');
            $sheet->getStyle('D' . $currentRow)->getFont()->setBold(true);
            $sheet->getStyle('D' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            
            // Calculate sum using formula
            $firstItemRow = $currentRow - $items->count();
            $lastItemRow = $currentRow - 1;
            $sheet->setCellValue('E' . $currentRow, "=SUM(E{$firstItemRow}:E{$lastItemRow})");
            $sheet->getStyle('E' . $currentRow)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('E' . $currentRow)->getFont()->setBold(true);
            
            // Add border
            $sheet->getStyle('D' . $currentRow . ':E' . $currentRow)->applyFromArray([
                'borders' => [
                    'top' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => '000000']],
                ],
            ]);
            
            $currentRow += 2; // Add spacing
            
            // Quotation Instructions section
            $sheet->setCellValue('A' . $currentRow, 'QUOTATION INSTRUCTIONS');
            $sheet->mergeCells('A' . $currentRow . ':E' . $currentRow);
            $sheet->getStyle('A' . $currentRow)->applyFromArray($headerStyle);
            $currentRow++;
            
            // Get quotation instructions from order or company settings
            $instructions = $order->quotation_instructions ?? $order->company->rfq_default_instructions ?? '';
            
            if ($instructions) {
                $sheet->setCellValue('A' . $currentRow, $instructions);
                $sheet->mergeCells('A' . $currentRow . ':E' . $currentRow);
                $sheet->getStyle('A' . $currentRow)->getAlignment()->setWrapText(true);
                $sheet->getRowDimension($currentRow)->setRowHeight(-1); // Auto height
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

        // Save to Documents History and get the document record
        $document = $this->saveToDocumentHistory($order, $filePath, $fileName);

        // Return the permanent storage path from the document record
        if ($document) {
            $permanentPath = storage_path('app/' . $document->file_path);
            \Log::info('RFQ Excel: Returning permanent path', [
                'permanent_path' => $permanentPath,
                'file_exists' => file_exists($permanentPath),
            ]);
            return $permanentPath;
        }
        
        \Log::warning('RFQ Excel: Document not saved, returning temp path', [
            'temp_path' => $filePath,
            'file_exists' => file_exists($filePath),
        ]);
        return $filePath;
    }

    /**
     * Save generated document to history
     *
     * @param Order $order
     * @param string $filePath
     * @param string $fileName
     * @return \App\Models\GeneratedDocument|null
     */
    protected function saveToDocumentHistory(Order $order, string $filePath, string $fileName): ?\App\Models\GeneratedDocument
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
            
            // Ensure directory exists
            $fullDirectory = storage_path('app/' . $directory);
            if (!file_exists($fullDirectory)) {
                mkdir($fullDirectory, 0755, true);
                \Log::info('RFQ: Created directory', ['directory' => $fullDirectory]);
            }
            
            // Copy file from temp to permanent storage using file_put_contents
            // This is more reliable than Storage::put() on some systems (macOS)
            $fullPath = storage_path('app/' . $storagePath);
            
            if (!copy($filePath, $fullPath)) {
                throw new \Exception("Failed to copy file to: {$fullPath}");
            }
            
            \Log::info('RFQ: File copied successfully', [
                'from' => $filePath,
                'to' => $fullPath,
                'file_exists' => file_exists($fullPath),
                'file_size' => filesize($fullPath),
            ]);
            
            // Create database record using the same pattern as PdfExportService
            $document = \App\Models\GeneratedDocument::createFromFile(
                $order,
                'rfq',
                'xlsx',
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
            
            return $document;
        } catch (\Exception $e) {
            \Log::error('Failed to save RFQ to document history', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return null;
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
