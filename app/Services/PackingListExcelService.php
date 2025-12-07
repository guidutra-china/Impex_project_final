<?php

namespace App\Services;

use App\Models\Shipment;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class PackingListExcelService
{
    /**
     * Generate Packing List Excel file
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

        // Styling
        $headerStyle = [
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563eb']], // Blue
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ];

        $labelStyle = [
            'font' => ['bold' => true, 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']],
        ];

        $tableHeaderStyle = [
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1f2937']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            ],
        ];

        $totalStyle = [
            'font' => ['bold' => true, 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D1FAE5']], // Light green
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '000000']],
            ],
        ];

        $currentRow = 1;

        // Title
        $sheet->setCellValue('A' . $currentRow, 'PACKING LIST');
        $sheet->mergeCells('A' . $currentRow . ':H' . $currentRow);
        $sheet->getStyle('A' . $currentRow)->applyFromArray($headerStyle);
        $sheet->getRowDimension($currentRow)->setRowHeight(35);
        $currentRow += 2;

        // Packing List Information - Use Commercial Invoice number and date
        $commercialInvoice = $shipment->commercialInvoice;
        $invoiceNumber = $commercialInvoice?->invoice_number ?? 'N/A';
        $invoiceDate = $commercialInvoice?->invoice_date ?? now();
        
        $sheet->setCellValue('A' . $currentRow, 'Invoice Number:');
        $sheet->setCellValue('B' . $currentRow, $invoiceNumber);
        $sheet->getStyle('A' . $currentRow)->applyFromArray($labelStyle);
        $currentRow++;

        $sheet->setCellValue('A' . $currentRow, 'Date:');
        $sheet->setCellValue('B' . $currentRow, $invoiceDate->format('d/m/Y'));
        $sheet->getStyle('A' . $currentRow)->applyFromArray($labelStyle);
        $currentRow += 2;

        // Exporter & Importer Info
        if ($displayOptions['show_exporter_details'] ?? true) {
            $companySettings = \App\Models\CompanySetting::first();
            $exporterName = $packingList->exporter_name ?? $companySettings?->company_name ?? 'N/A';
            $exporterAddress = $packingList->exporter_address ?? $companySettings?->full_address ?? 'N/A';
            
            $sheet->setCellValue('A' . $currentRow, 'EXPORTER (SHIPPER):');
            $sheet->getStyle('A' . $currentRow)->applyFromArray($labelStyle);
            $currentRow++;
            $sheet->setCellValue('A' . $currentRow, $exporterName);
            $currentRow++;
            $sheet->setCellValue('A' . $currentRow, $exporterAddress);
            $currentRow += 2;
        }

        if ($displayOptions['show_importer_details'] ?? true) {
            $customer = $shipment->customer;
            $importerName = $packingList->importer_name ?? $customer?->name ?? 'N/A';
            $importerAddress = $packingList->importer_address ?? ($customer ? implode(', ', array_filter([$customer->address, $customer->city, $customer->state, $customer->zip, $customer->country])) : 'N/A');
            
            $sheet->setCellValue('A' . $currentRow, 'IMPORTER (CONSIGNEE):');
            $sheet->getStyle('A' . $currentRow)->applyFromArray($labelStyle);
            $currentRow++;
            $sheet->setCellValue('A' . $currentRow, $importerName);
            $currentRow++;
            $sheet->setCellValue('A' . $currentRow, $importerAddress);
            $currentRow += 2;
        }

        // Shipping Details
        if ($displayOptions['show_shipping_details'] ?? true) {
            $sheet->setCellValue('A' . $currentRow, 'SHIPPING DETAILS:');
            $sheet->getStyle('A' . $currentRow)->applyFromArray($labelStyle);
            $currentRow++;
            
            $sheet->setCellValue('A' . $currentRow, 'Port of Loading:');
            $sheet->setCellValue('B' . $currentRow, $packingList->port_of_loading ?? $shipment->port_of_loading ?? 'N/A');
            $currentRow++;
            
            $sheet->setCellValue('A' . $currentRow, 'Port of Discharge:');
            $sheet->setCellValue('B' . $currentRow, $packingList->port_of_discharge ?? $shipment->port_of_discharge ?? 'N/A');
            $currentRow++;
            
            $sheet->setCellValue('A' . $currentRow, 'Final Destination:');
            $sheet->setCellValue('B' . $currentRow, $packingList->final_destination ?? $shipment->final_destination ?? 'N/A');
            $currentRow++;
            
            if ($packingList->bl_number ?? $shipment->bl_number) {
                $sheet->setCellValue('A' . $currentRow, 'B/L Number:');
                $sheet->setCellValue('B' . $currentRow, $packingList->bl_number ?? $shipment->bl_number);
                $currentRow++;
            }
            
            if ($packingList->container_numbers) {
                $sheet->setCellValue('A' . $currentRow, 'Container Numbers:');
                $sheet->setCellValue('B' . $currentRow, $packingList->container_numbers);
                $currentRow++;
            }
            
            $currentRow += 2;
        }

        // Items Table Header
        $col = 'A';
        $sheet->setCellValue($col . $currentRow, 'No.');
        $sheet->getColumnDimension($col)->setWidth(6);
        $col++;
        
        $sheet->setCellValue($col . $currentRow, 'Product Description');
        $sheet->getColumnDimension($col)->setWidth(35);
        $col++;
        
        if ($displayOptions['show_supplier_code'] ?? false) {
            $sheet->setCellValue($col . $currentRow, 'Supplier Code');
            $sheet->getColumnDimension($col)->setWidth(15);
            $col++;
        }
        
        if ($displayOptions['show_hs_codes'] ?? true) {
            $sheet->setCellValue($col . $currentRow, 'HS Code');
            $sheet->getColumnDimension($col)->setWidth(12);
            $col++;
        }
        
        if ($displayOptions['show_country_of_origin'] ?? true) {
            $sheet->setCellValue($col . $currentRow, 'Origin');
            $sheet->getColumnDimension($col)->setWidth(12);
            $col++;
        }
        
        $sheet->setCellValue($col . $currentRow, 'Qty');
        $sheet->getColumnDimension($col)->setWidth(10);
        $col++;
        
        $sheet->setCellValue($col . $currentRow, 'Cartons');
        $sheet->getColumnDimension($col)->setWidth(10);
        $col++;
        
        if ($displayOptions['show_weight_volume'] ?? true) {
            $sheet->setCellValue($col . $currentRow, 'N.W. (kg)');
            $sheet->getColumnDimension($col)->setWidth(12);
            $col++;
            
            $sheet->setCellValue($col . $currentRow, 'G.W. (kg)');
            $sheet->getColumnDimension($col)->setWidth(12);
            $col++;
            
            $sheet->setCellValue($col . $currentRow, 'CBM');
            $sheet->getColumnDimension($col)->setWidth(12);
            $col++;
        }
        
        $lastCol = chr(ord($col) - 1);
        $sheet->getStyle('A' . $currentRow . ':' . $lastCol . $currentRow)->applyFromArray($tableHeaderStyle);
        $currentRow++;

        // Items Data
        $containers = $shipment->containers()->with('items.product')->get();
        $itemNumber = 1;
        $totalQty = 0;
        $totalCartons = 0;
        $totalNetWeight = 0;
        $totalGrossWeight = 0;
        $totalVolume = 0;

        foreach ($containers as $container) {
            foreach ($container->items as $item) {
                $product = $item->product;
                $qty = $item->quantity ?? 0;
                
                // Calculate cartons: quantity / pcs_per_carton (rounded up)
                $pcsPerCarton = $product->pcs_per_carton ?? 1;
                $cartons = $pcsPerCarton > 0 ? ceil($qty / $pcsPerCarton) : 0;
                
                // Use total_volume from item (already calculated) or calculate from product
                $volume = $item->total_volume ?? (($product->volume ?? 0) * $qty);
                
                // Calculate weights
                $netWeight = ($product->net_weight ?? 0) * $qty;
                $grossWeight = ($product->gross_weight ?? 0) * $qty;
                
                $totalQty += $qty;
                $totalCartons += $cartons;
                $totalNetWeight += $netWeight;
                $totalGrossWeight += $grossWeight;
                $totalVolume += $volume;

                $col = 'A';
                $sheet->setCellValue($col . $currentRow, $itemNumber++);
                $col++;
                
                $sheet->setCellValue($col . $currentRow, $product->name);
                $col++;
                
                if ($displayOptions['show_supplier_code'] ?? false) {
                    $sheet->setCellValue($col . $currentRow, $product->supplier_code ?? 'N/A');
                    $col++;
                }
                
                if ($displayOptions['show_hs_codes'] ?? true) {
                    $sheet->setCellValue($col . $currentRow, $product->hs_code ?? 'N/A');
                    $col++;
                }
                
                if ($displayOptions['show_country_of_origin'] ?? true) {
                    $sheet->setCellValue($col . $currentRow, $product->country_of_origin ?? 'N/A');
                    $col++;
                }
                
                $sheet->setCellValue($col . $currentRow, $qty);
                $col++;
                
                $sheet->setCellValue($col . $currentRow, $cartons);
                $col++;
                
                if ($displayOptions['show_weight_volume'] ?? true) {
                    $sheet->setCellValue($col . $currentRow, number_format($netWeight, 2));
                    $col++;
                    
                    $sheet->setCellValue($col . $currentRow, number_format($grossWeight, 2));
                    $col++;
                    
                    $sheet->setCellValue($col . $currentRow, number_format($volume, 3));
                    $col++;
                }
                
                // Apply borders to row
                $sheet->getStyle('A' . $currentRow . ':' . $lastCol . $currentRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']],
                    ],
                ]);
                
                $currentRow++;
            }
        }

        // Totals Row
        $col = 'A';
        $totalStartCol = $col;
        
        // Calculate colspan for "TOTAL" label
        $colsBeforeQty = 1; // No. column
        if ($displayOptions['show_supplier_code'] ?? false) $colsBeforeQty++;
        if ($displayOptions['show_hs_codes'] ?? true) $colsBeforeQty++;
        if ($displayOptions['show_country_of_origin'] ?? true) $colsBeforeQty++;
        
        $totalLabelEndCol = chr(ord('A') + $colsBeforeQty);
        $sheet->setCellValue('A' . $currentRow, 'TOTAL:');
        $sheet->mergeCells('A' . $currentRow . ':' . $totalLabelEndCol . $currentRow);
        
        $col = chr(ord($totalLabelEndCol) + 1);
        $sheet->setCellValue($col . $currentRow, $totalQty);
        $col++;
        
        $sheet->setCellValue($col . $currentRow, $totalCartons);
        $col++;
        
        if ($displayOptions['show_weight_volume'] ?? true) {
            $sheet->setCellValue($col . $currentRow, number_format($totalNetWeight, 2));
            $col++;
            
            $sheet->setCellValue($col . $currentRow, number_format($totalGrossWeight, 2));
            $col++;
            
            $sheet->setCellValue($col . $currentRow, number_format($totalVolume, 3));
            $col++;
        }
        
        $sheet->getStyle('A' . $currentRow . ':' . $lastCol . $currentRow)->applyFromArray($totalStyle);
        $currentRow += 2;

        // Notes
        if ($packingList->notes) {
            $sheet->setCellValue('A' . $currentRow, 'NOTES:');
            $sheet->getStyle('A' . $currentRow)->applyFromArray($labelStyle);
            $currentRow++;
            $sheet->setCellValue('A' . $currentRow, $packingList->notes);
            $sheet->mergeCells('A' . $currentRow . ':' . $lastCol . $currentRow);
            $sheet->getStyle('A' . $currentRow)->getAlignment()->setWrapText(true);
        }

        // Save file
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
}
