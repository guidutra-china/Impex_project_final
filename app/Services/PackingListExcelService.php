<?php

namespace App\Services;

use App\Models\Shipment;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class PackingListExcelService
{
    /**
     * Generate Packing List Excel file with formulas
     *
     * @param Shipment $shipment
     * @return string Path to generated file
     */
    public function generate(Shipment $shipment): string
    {
        $packingList = $shipment->packingList;
        $displayOptions = $packingList->display_options ?? [];
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator('Impex System')
            ->setTitle('Packing List - ' . ($packingList->packing_list_number ?? 'PL-' . $shipment->shipment_number))
            ->setSubject('Packing List');

        // Define styles
        $styles = $this->getStyles();
        
        $currentRow = 1;
        $logoRow = $currentRow; // Save for logo (will add after we know lastCol)
        $currentRow++;
        
        $titleRow = $currentRow; // Save for later update after we know last column
        $currentRow += 2;

        // ==================== DOCUMENT INFO ====================
        $commercialInvoice = $shipment->commercialInvoice;
        $invoiceNumber = $commercialInvoice?->invoice_number ?? 'N/A';
        $invoiceDate = $commercialInvoice?->invoice_date ?? now();
        
        $sheet->setCellValue('A' . $currentRow, 'Invoice Number:');
        $sheet->setCellValue('B' . $currentRow, $invoiceNumber);
        $sheet->getStyle('A' . $currentRow)->applyFromArray($styles['label']);
        $sheet->getStyle('B' . $currentRow)->applyFromArray($styles['value']);
        $currentRow++;

        $sheet->setCellValue('A' . $currentRow, 'Date:');
        $sheet->setCellValue('B' . $currentRow, $invoiceDate->format('d/m/Y'));
        $sheet->getStyle('A' . $currentRow)->applyFromArray($styles['label']);
        $sheet->getStyle('B' . $currentRow)->applyFromArray($styles['value']);
        $currentRow += 2;

        // ==================== EXPORTER & IMPORTER ====================
        $infoStartRow = $currentRow;
        
        // Prepare Exporter data
        $exporterData = [];
        if ($displayOptions['show_exporter_details'] ?? true) {
            $companySettings = \App\Models\CompanySetting::first();
            $exporterData = [
                'name' => $packingList->exporter_name ?? $companySettings?->company_name ?? 'N/A',
                'address' => $packingList->exporter_address ?? $companySettings?->full_address ?? 'N/A',
            ];
        }
        
        // Prepare Importer data
        $importerData = [];
        if ($displayOptions['show_importer_details'] ?? true) {
            $customer = $shipment->customer;
            $importerData = [
                'name' => $packingList->importer_name ?? $customer?->name ?? 'N/A',
                'address' => $packingList->importer_address ?? $customer?->address ?? 'N/A',
            ];
        }
        
        // Reserve space for exporter/importer (3 rows each)
        if (!empty($exporterData) || !empty($importerData)) {
            $currentRow += 3;
        }
        
        $currentRow = max($currentRow, $infoStartRow + 4);
        
        // Save row for shipping details (will fill after we know lastCol)
        $shippingDetailsRow = $currentRow;
        $shippingDetailsData = [];
        
        if ($displayOptions['show_shipping_details'] ?? true) {
            $shippingDetailsData = [
                'Port of Loading:' => $packingList->port_of_loading ?? $shipment->origin_port ?? 'N/A',
                'Port of Discharge:' => $packingList->port_of_discharge ?? $shipment->destination_port ?? 'N/A',
                'Final Destination:' => $packingList->final_destination ?? $shipment->final_destination ?? 'N/A',
            ];
            
            if ($packingList->bl_number ?? $shipment->bl_number) {
                $shippingDetailsData['B/L Number:'] = $packingList->bl_number ?? $shipment->bl_number;
            }
            
            if ($packingList->container_numbers) {
                $shippingDetailsData['Container Numbers:'] = $packingList->container_numbers;
            }
            
            // Reserve space for shipping details
            $currentRow += count($shippingDetailsData) + 2; // +1 for header, +1 for spacing
        }

        // ==================== ITEMS TABLE ====================
        $tableStartRow = $currentRow;
        
        // Now we can set the title with correct column span
        $sheet->setCellValue('A' . $titleRow, 'PACKING LIST');
        // Will update merge after we know lastCol
        
        // Table Header
        $col = 'A';
        $headers = [];
        
        $headers[] = ['col' => $col++, 'label' => 'No.', 'width' => 6];
        
        if ($displayOptions['show_customer_code'] ?? true) {
            $headers[] = ['col' => $col++, 'label' => 'Customer Code', 'width' => 15];
        }
        
        $headers[] = ['col' => $col++, 'label' => 'Product Description', 'width' => 35];
        $qtyCol = $col;
        $headers[] = ['col' => $col++, 'label' => 'Qty', 'width' => 10];
        $qtyCartonCol = $col;
        $headers[] = ['col' => $col++, 'label' => 'Qty/Carton', 'width' => 12];
        $cartonsCol = $col;
        $headers[] = ['col' => $col++, 'label' => 'Cartons', 'width' => 10];
        
        if ($displayOptions['show_weight_volume'] ?? true) {
            $nwUnitCol = $col;
            $headers[] = ['col' => $col++, 'label' => 'N.W. Unit (kg)', 'width' => 12];
            $gwUnitCol = $col;
            $headers[] = ['col' => $col++, 'label' => 'G.W. Unit (kg)', 'width' => 12];
            $nwCol = $col;
            $headers[] = ['col' => $col++, 'label' => 'Total N.W. (kg)', 'width' => 12];
            $gwCol = $col;
            $headers[] = ['col' => $col++, 'label' => 'Total G.W. (kg)', 'width' => 12];
            $cbmCol = $col;
            $headers[] = ['col' => $col++, 'label' => 'CBM', 'width' => 12];
        }
        
        $lastCol = chr(ord($col) - 1);
        
        // ==================== ADD LOGO NOW ====================
        $companySettings = \App\Models\CompanySetting::first();
        $logoPath = $companySettings?->logo_full_path;
        
        if ($logoPath && file_exists($logoPath)) {
            try {
                $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                $drawing->setName('Company Logo');
                $drawing->setDescription('Company Logo');
                $drawing->setPath($logoPath);
                $drawing->setHeight(60); // Logo height in pixels
                
                // Calculate center position
                // Each column is approximately 8.43 characters wide
                $totalCols = ord($lastCol) - ord('A') + 1;
                $centerCol = chr(ord('A') + floor($totalCols / 2));
                
                $drawing->setCoordinates($centerCol . $logoRow);
                $drawing->setOffsetX(10); // Small offset for better centering
                $drawing->setWorksheet($sheet);
                
                $sheet->getRowDimension($logoRow)->setRowHeight(50);
            } catch (\Exception $e) {
                // If logo fails, just skip it
            }
        } else {
            // No logo, reduce row height
            $sheet->getRowDimension($logoRow)->setRowHeight(15);
        }
        
        // Merge logo row for consistent styling
        $sheet->mergeCells('A' . $logoRow . ':' . $lastCol . $logoRow);
        $sheet->getStyle('A' . $logoRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Update title merge now that we know the last column
        $sheet->mergeCells('A' . $titleRow . ':' . $lastCol . $titleRow);
        $sheet->getStyle('A' . $titleRow)->applyFromArray($styles['title']);
        $sheet->getRowDimension($titleRow)->setRowHeight(40);
        
        // ==================== FILL EXPORTER & IMPORTER NOW ====================
        // Calculate middle column for splitting
        $midCol = chr(ord('A') + floor((ord($lastCol) - ord('A')) / 2));
        
        if (!empty($exporterData) || !empty($importerData)) {
            $currentInfoRow = $infoStartRow;
            
            // Exporter (Left side: A to midCol)
            if (!empty($exporterData)) {
                $sheet->setCellValue('A' . $currentInfoRow, 'EXPORTER (SHIPPER):');
                $sheet->mergeCells('A' . $currentInfoRow . ':' . $midCol . $currentInfoRow);
                $sheet->getStyle('A' . $currentInfoRow)->applyFromArray($styles['sectionHeader']);
                $currentInfoRow++;
                
                $sheet->setCellValue('A' . $currentInfoRow, $exporterData['name']);
                $sheet->mergeCells('A' . $currentInfoRow . ':' . $midCol . $currentInfoRow);
                $currentInfoRow++;
                
                $sheet->setCellValue('A' . $currentInfoRow, $exporterData['address']);
                $sheet->mergeCells('A' . $currentInfoRow . ':' . $midCol . $currentInfoRow);
                $sheet->getStyle('A' . $currentInfoRow)->getAlignment()->setWrapText(true);
            }
            
            // Importer (Right side: after midCol to lastCol)
            $importerStartCol = chr(ord($midCol) + 1);
            $currentInfoRow = $infoStartRow;
            
            if (!empty($importerData)) {
                $sheet->setCellValue($importerStartCol . $currentInfoRow, 'IMPORTER (CONSIGNEE):');
                $sheet->mergeCells($importerStartCol . $currentInfoRow . ':' . $lastCol . $currentInfoRow);
                $sheet->getStyle($importerStartCol . $currentInfoRow)->applyFromArray($styles['sectionHeader']);
                $currentInfoRow++;
                
                $sheet->setCellValue($importerStartCol . $currentInfoRow, $importerData['name']);
                $sheet->mergeCells($importerStartCol . $currentInfoRow . ':' . $lastCol . $currentInfoRow);
                $currentInfoRow++;
                
                $sheet->setCellValue($importerStartCol . $currentInfoRow, $importerData['address']);
                $sheet->mergeCells($importerStartCol . $currentInfoRow . ':' . $lastCol . $currentInfoRow);
                $sheet->getStyle($importerStartCol . $currentInfoRow)->getAlignment()->setWrapText(true);
            }
        }
        
        // ==================== FILL SHIPPING DETAILS NOW ====================
        if (!empty($shippingDetailsData)) {
            $currentShippingRow = $shippingDetailsRow;
            
            $sheet->setCellValue('A' . $currentShippingRow, 'SHIPPING DETAILS:');
            $sheet->mergeCells('A' . $currentShippingRow . ':' . $lastCol . $currentShippingRow);
            $sheet->getStyle('A' . $currentShippingRow)->applyFromArray($styles['sectionHeader']);
            $currentShippingRow++;
            
            foreach ($shippingDetailsData as $label => $value) {
                // Merge A:B for label
                $sheet->setCellValue('A' . $currentShippingRow, $label);
                $sheet->mergeCells('A' . $currentShippingRow . ':B' . $currentShippingRow);
                $sheet->getStyle('A' . $currentShippingRow)->applyFromArray($styles['label']);
                
                // Value in column C
                $sheet->setCellValue('C' . $currentShippingRow, $value);
                $sheet->mergeCells('C' . $currentShippingRow . ':' . $lastCol . $currentShippingRow);
                $sheet->getStyle('C' . $currentShippingRow)->applyFromArray($styles['value']);
                $currentShippingRow++;
            }
        }
        
        // Apply headers
        foreach ($headers as $header) {
            $sheet->setCellValue($header['col'] . $currentRow, $header['label']);
            $sheet->getColumnDimension($header['col'])->setWidth($header['width']);
        }
        
        $sheet->getStyle('A' . $currentRow . ':' . $lastCol . $currentRow)->applyFromArray($styles['tableHeader']);
        $currentRow++;
        
        // ==================== ITEMS DATA ====================
        $itemStartRow = $currentRow;
        $itemNumber = 1;
        
        foreach ($shipment->containers as $container) {
            foreach ($container->items as $item) {
                $product = $item->product;
                $qty = $item->quantity;
                $pcsPerCarton = $product->pcs_per_carton ?? 1;
                $cartons = $pcsPerCarton > 0 ? ceil($qty / $pcsPerCarton) : 0;
                
                // Weights and volume
                $netWeight = ($product->net_weight ?? 0) * $qty;
                $grossWeight = ($product->gross_weight ?? 0) * $qty;
                $volume = $item->total_volume ?? (($product->volume ?? 0) * $qty);
                
                $col = 'A';
                
                // No.
                $sheet->setCellValue($col++ . $currentRow, $itemNumber++);
                
                // Customer Code
                if ($displayOptions['show_customer_code'] ?? true) {
                    $sheet->setCellValue($col++ . $currentRow, $product->customer_code ?? 'N/A');
                }
                
                // Product Description
                $sheet->setCellValue($col++ . $currentRow, $product->name);
                
                // Qty
                $sheet->setCellValue($col++ . $currentRow, $qty);
                
                // Qty/Carton
                $sheet->setCellValue($col++ . $currentRow, $pcsPerCarton);
                
                // Cartons
                $sheet->setCellValue($col++ . $currentRow, $cartons);
                
                // Weights and Volume
                if ($displayOptions['show_weight_volume'] ?? true) {
                    $sheet->setCellValue($col++ . $currentRow, $product->net_weight ?? 0);
                    $sheet->setCellValue($col++ . $currentRow, $product->gross_weight ?? 0);
                    $sheet->setCellValue($col++ . $currentRow, $netWeight);
                    $sheet->setCellValue($col++ . $currentRow, $grossWeight);
                    $sheet->setCellValue($col++ . $currentRow, $volume);
                }
                
                // Apply borders and alignment
                $sheet->getStyle('A' . $currentRow . ':' . $lastCol . $currentRow)->applyFromArray($styles['tableCell']);
                $sheet->getStyle($qtyCol . $currentRow . ':' . $lastCol . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                $currentRow++;
            }
        }
        
        $itemEndRow = $currentRow - 1;
        
        // ==================== TOTALS ROW WITH FORMULAS ====================
        $sheet->setCellValue('A' . $currentRow, 'TOTAL:');
        
        // Calculate colspan for TOTAL label
        $colspan = 1; // No.
        if ($displayOptions['show_customer_code'] ?? true) $colspan++;
        $colspan++; // Description
        
        $totalLabelEndCol = chr(ord('A') + $colspan - 1);
        $sheet->mergeCells('A' . $currentRow . ':' . $totalLabelEndCol . $currentRow);
        
        // Qty Total (SUM formula)
        $sheet->setCellValue($qtyCol . $currentRow, "=SUM({$qtyCol}{$itemStartRow}:{$qtyCol}{$itemEndRow})");
        
        // Qty/Carton (no total, just dash)
        $sheet->setCellValue($qtyCartonCol . $currentRow, '-');
        
        // Cartons Total (SUM formula)
        $sheet->setCellValue($cartonsCol . $currentRow, "=SUM({$cartonsCol}{$itemStartRow}:{$cartonsCol}{$itemEndRow})");
        
        // Weights and Volume Totals (SUM formulas)
        if ($displayOptions['show_weight_volume'] ?? true) {
            // Unit weights: no total (dash)
            $sheet->setCellValue($nwUnitCol . $currentRow, '-');
            $sheet->setCellValue($gwUnitCol . $currentRow, '-');
            
            // Total weights: SUM formulas
            $sheet->setCellValue($nwCol . $currentRow, "=SUM({$nwCol}{$itemStartRow}:{$nwCol}{$itemEndRow})");
            $sheet->setCellValue($gwCol . $currentRow, "=SUM({$gwCol}{$itemStartRow}:{$gwCol}{$itemEndRow})");
            $sheet->setCellValue($cbmCol . $currentRow, "=SUM({$cbmCol}{$itemStartRow}:{$cbmCol}{$itemEndRow})");
            
            // Format numbers
            $sheet->getStyle($nwCol . $currentRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
            $sheet->getStyle($gwCol . $currentRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
            $sheet->getStyle($cbmCol . $currentRow)->getNumberFormat()->setFormatCode('#,##0.000');
        }
        
        $sheet->getStyle('A' . $currentRow . ':' . $lastCol . $currentRow)->applyFromArray($styles['totalRow']);
        $currentRow += 2;

        // ==================== NOTES ====================
        if ($packingList->notes) {
            $sheet->setCellValue('A' . $currentRow, 'NOTES:');
            $sheet->getStyle('A' . $currentRow)->applyFromArray($styles['sectionHeader']);
            $currentRow++;
            
            $sheet->setCellValue('A' . $currentRow, $packingList->notes);
            $sheet->mergeCells('A' . $currentRow . ':' . $lastCol . $currentRow);
            $sheet->getStyle('A' . $currentRow)->getAlignment()->setWrapText(true);
        }

        // ==================== SAVE FILE ====================
        $fileName = 'packing_list_' . ($packingList->packing_list_number ?? $shipment->shipment_number) . '_' . now()->format('YmdHis') . '.xlsx';
        $filePath = storage_path('app/public/exports/' . $fileName);
        
        // Ensure directory exists
        if (!file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        return $filePath;
    }
    
    /**
     * Get predefined styles
     */
    private function getStyles(): array
    {
        return [
            'title' => [
                'font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563eb']], // Blue
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ],
            'label' => [
                'font' => ['bold' => true, 'size' => 10],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ],
            'value' => [
                'font' => ['size' => 10],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ],
            'sectionHeader' => [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1f2937']], // Dark gray
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            ],
            'tableHeader' => [
                'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1f2937']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                ],
            ],
            'tableCell' => [
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']],
                ],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ],
            'totalRow' => [
                'font' => ['bold' => true, 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DBEAFE']], // Light blue
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '000000']],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ],
        ];
    }
}
