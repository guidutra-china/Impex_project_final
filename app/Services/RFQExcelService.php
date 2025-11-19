<?php

namespace App\Services;

use App\Models\Order;
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
     * @return string Path to generated file
     */
    public function generateRFQ(Order $order): string
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
        $items = $order->items()->with(['product', 'product.features'])->get();

        // Always add ORDER ITEMS section
        // Items header
        $sheet->setCellValue('A' . $currentRow, $items->isNotEmpty() ? 'ORDER ITEMS' : 'ORDER ITEMS (To be filled by supplier)');
        $sheet->mergeCells('A' . $currentRow . ':E' . $currentRow);
        $sheet->getStyle('A' . $currentRow)->applyFromArray($headerStyle);
        $currentRow++;

        // Table headers
        $sheet->setCellValue('A' . $currentRow, 'Product Name');
        $sheet->setCellValue('B' . $currentRow, 'Quantity');
        $sheet->setCellValue('C' . $currentRow, $items->isNotEmpty() ? 'Target Price' : 'Unit Price');
        $sheet->setCellValue('D' . $currentRow, 'Supplier Price');
        $sheet->setCellValue('E' . $currentRow, $items->isNotEmpty() ? 'Features' : 'Description / Features');
        $sheet->getStyle('A' . $currentRow . ':E' . $currentRow)->applyFromArray($labelStyle);
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
                    ? number_format($item->requested_unit_price / 100, 2)
                    : 'N/A';
                $sheet->setCellValue('C' . $currentRow, $targetPrice);
                
                // Supplier Price (empty for supplier to fill)
                $sheet->setCellValue('D' . $currentRow, '');
                $sheet->getStyle('D' . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFCC'); // Light yellow
                
                // Features
                $features = $item->product->features ?? collect();
                if ($features->isNotEmpty()) {
                    $featuresList = $features->map(function ($feature) {
                        return "• {$feature->name}: {$feature->value}";
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
            // No items - add empty rows for supplier to fill
            $emptyRowsCount = 15; // Number of empty rows to add
            
            for ($i = 0; $i < $emptyRowsCount; $i++) {
                // All cells are empty and editable
                $sheet->setCellValue('A' . $currentRow, '');
                $sheet->setCellValue('B' . $currentRow, '');
                $sheet->setCellValue('C' . $currentRow, '');
                $sheet->setCellValue('D' . $currentRow, '');
                $sheet->setCellValue('E' . $currentRow, '');
                
                // Highlight all cells in yellow to indicate they should be filled
                $sheet->getStyle('A' . $currentRow . ':E' . $currentRow)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('FFFFCC'); // Light yellow
                
                // Apply borders
                $sheet->getStyle('A' . $currentRow . ':E' . $currentRow)->applyFromArray([
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
        $fileName = 'RFQ_' . $order->order_number . '_' . time() . '.xlsx';
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

        return $filePath;
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
