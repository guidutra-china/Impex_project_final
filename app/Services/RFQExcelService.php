<?php

namespace App\Services;

use App\Models\Order;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

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
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ];

        $labelStyle = [
            'font' => ['bold' => true, 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E7E6E6']],
        ];

        // Title
        $sheet->setCellValue('A1', 'REQUEST FOR QUOTATION');
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $currentRow = 3;

        // RFQ Number
        $sheet->setCellValue('A' . $currentRow, 'RFQ Number:');
        $sheet->setCellValue('B' . $currentRow, $order->order_number);
        $sheet->getStyle('A' . $currentRow)->applyFromArray($labelStyle);
        $currentRow++;

        // Supplier Name (placeholder - to be filled when sending to specific supplier)
        $sheet->setCellValue('A' . $currentRow, 'Supplier Name:');
        $sheet->setCellValue('B' . $currentRow, '[To be filled]');
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
        $sheet->mergeCells('A' . $currentRow . ':D' . $currentRow);
        $sheet->getStyle('A' . $currentRow)->getAlignment()->setWrapText(true);
        $sheet->getRowDimension($currentRow)->setRowHeight(60);
        $currentRow += 2;

        // Order Items
        $items = $order->items()->with(['product', 'product.features'])->get();

        if ($items->isNotEmpty()) {
            // Items header
            $sheet->setCellValue('A' . $currentRow, 'ORDER ITEMS');
            $sheet->mergeCells('A' . $currentRow . ':D' . $currentRow);
            $sheet->getStyle('A' . $currentRow)->applyFromArray($headerStyle);
            $currentRow++;

            // Table headers
            $sheet->setCellValue('A' . $currentRow, 'Product Name');
            $sheet->setCellValue('B' . $currentRow, 'Quantity');
            $sheet->setCellValue('C' . $currentRow, 'Target Price');
            $sheet->setCellValue('D' . $currentRow, 'Features');
            $sheet->getStyle('A' . $currentRow . ':D' . $currentRow)->applyFromArray($labelStyle);
            $currentRow++;

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
                
                // Features
                $features = $item->product->features ?? collect();
                if ($features->isNotEmpty()) {
                    $featuresList = $features->map(function ($feature) {
                        $display = "• {$feature->feature_name}: {$feature->feature_value}";
                        if ($feature->unit) {
                            $display .= " {$feature->unit}";
                        }
                        return $display;
                    })->implode("\n");
                    $sheet->setCellValue('D' . $currentRow, $featuresList);
                    $sheet->getStyle('D' . $currentRow)->getAlignment()->setWrapText(true);
                    
                    // Adjust row height based on number of features
                    $rowHeight = max(30, $features->count() * 15);
                    $sheet->getRowDimension($currentRow)->setRowHeight($rowHeight);
                } else {
                    $sheet->setCellValue('D' . $currentRow, 'No features');
                }

                // Apply borders
                $sheet->getStyle('A' . $currentRow . ':D' . $currentRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                    ],
                ]);

                $currentRow++;
            }
        }

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(40);

        // Generate file
        $fileName = 'RFQ_' . $order->order_number . '_' . time() . '.xlsx';
        $filePath = storage_path('app/temp/' . $fileName);

        // Ensure temp directory exists
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        return $filePath;
    }
}
