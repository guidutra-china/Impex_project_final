<?php
namespace App\Services;

use App\Models\Product;
use App\Models\BomVersion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class BomExportService
{
    /**
     * Export current BOM to PDF
     */
    public function exportCurrentBomToPdf(Product $product): string
    {
        $data = [
            'product' => $product->load('bomItems.component', 'supplier', 'customer'),
            'bomItems' => $product->bomItems,
            'exportDate' => now()->format('M d, Y H:i'),
        ];

        $pdf = Pdf::loadView('exports.bom-pdf', $data);
        $pdf->setPaper('a4', 'portrait');

        $filename = "BOM_{$product->sku}_{$product->name}_" . now()->format('Ymd_His') . ".pdf";
        $path = "exports/bom/{$filename}";

        Storage::put($path, $pdf->output());

        return $path;
    }

    /**
     * Export BOM version to PDF
     */
    public function exportVersionToPdf(BomVersion $version): string
    {
        $data = [
            'version' => $version->load('bomVersionItems.component', 'product'),
            'product' => $version->product,
            'bomItems' => $version->bomVersionItems,
            'exportDate' => now()->format('M d, Y H:i'),
        ];

        $pdf = Pdf::loadView('exports.bom-version-pdf', $data);
        $pdf->setPaper('a4', 'portrait');

        $filename = "BOM_{$version->product->sku}_{$version->version_display}_" . now()->format('Ymd_His') . ".pdf";
        $path = "exports/bom/{$filename}";

        Storage::put($path, $pdf->output());

        return $path;
    }

    /**
     * Export current BOM to Excel
     */
    public function exportCurrentBomToExcel(Product $product): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set title
        $sheet->setTitle('BOM');

        // Header Section
        $sheet->setCellValue('A1', 'Bill of Materials');
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setSize(16)->setBold(true);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Product Information
        $row = 3;
        $sheet->setCellValue("A{$row}", 'Product:');
        $sheet->setCellValue("B{$row}", $product->name);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);

        $row++;
        $sheet->setCellValue("A{$row}", 'SKU:');
        $sheet->setCellValue("B{$row}", $product->sku);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);

        $row++;
        $sheet->setCellValue("A{$row}", 'Export Date:');
        $sheet->setCellValue("B{$row}", now()->format('M d, Y H:i'));
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);

        // BOM Table Header
        $row += 2;
        $headerRow = $row;
        $headers = ['Code', 'Component', 'Quantity', 'UOM', 'Waste %', 'Actual Qty', 'Unit Cost', 'Total Cost'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue("{$col}{$row}", $header);
            $sheet->getStyle("{$col}{$row}")->getFont()->setBold(true);
            $sheet->getStyle("{$col}{$row}")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('4B5563');
            $sheet->getStyle("{$col}{$row}")->getFont()->getColor()->setRGB('FFFFFF');
            $col++;
        }

        // BOM Items
        $row++;
        foreach ($product->bomItems as $item) {
            $sheet->setCellValue("A{$row}", $item->component->code);
            $sheet->setCellValue("B{$row}", $item->component->name);
            $sheet->setCellValue("C{$row}", $item->quantity);
            $sheet->setCellValue("D{$row}", $item->unit_of_measure);
            $sheet->setCellValue("E{$row}", $item->waste_factor);
            $sheet->setCellValue("F{$row}", $item->actual_quantity);
            $sheet->setCellValue("G{$row}", $item->unit_cost / 100);
            $sheet->setCellValue("H{$row}", $item->total_cost / 100);

            // Format currency
            $sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('$#,##0.00');

            $row++;
        }

        // Totals
        $row++;
        $sheet->setCellValue("G{$row}", 'BOM Material Cost:');
        $sheet->setCellValue("H{$row}", $product->bom_material_cost / 100);
        $sheet->getStyle("G{$row}:H{$row}")->getFont()->setBold(true);
        $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('$#,##0.00');

        $row++;
        $sheet->setCellValue("G{$row}", 'Direct Labor:');
        $sheet->setCellValue("H{$row}", $product->direct_labor_cost / 100);
        $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('$#,##0.00');

        $row++;
        $sheet->setCellValue("G{$row}", 'Direct Overhead:');
        $sheet->setCellValue("H{$row}", $product->direct_overhead_cost / 100);
        $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('$#,##0.00');

        $row++;
        $sheet->setCellValue("G{$row}", 'Total Manufacturing Cost:');
        $sheet->setCellValue("H{$row}", $product->total_manufacturing_cost / 100);
        $sheet->getStyle("G{$row}:H{$row}")->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('$#,##0.00');
        $sheet->getStyle("H{$row}")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('D1FAE5');

        // Auto-size columns
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Borders
        $lastRow = $row;
        $sheet->getStyle("A{$headerRow}:H{$lastRow}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        // Save file
        $filename = "BOM_{$product->sku}_{$product->name}_" . now()->format('Ymd_His') . ".xlsx";
        $path = storage_path("app/exports/bom/{$filename}");

        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return "exports/bom/{$filename}";
    }

    /**
     * Export BOM version to Excel
     */
    public function exportVersionToExcel(BomVersion $version): string
    {
        $product = $version->product;
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set title
        $sheet->setTitle('BOM Version');

        // Header Section
        $sheet->setCellValue('A1', 'Bill of Materials - ' . $version->version_display);
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setSize(16)->setBold(true);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Product & Version Information
        $row = 3;
        $sheet->setCellValue("A{$row}", 'Product:');
        $sheet->setCellValue("B{$row}", $product->name);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);

        $row++;
        $sheet->setCellValue("A{$row}", 'SKU:');
        $sheet->setCellValue("B{$row}", $product->sku);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);

        $row++;
        $sheet->setCellValue("A{$row}", 'Version:');
        $sheet->setCellValue("B{$row}", $version->version_display);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);

        $row++;
        $sheet->setCellValue("A{$row}", 'Status:');
        $sheet->setCellValue("B{$row}", ucfirst($version->status));
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);

        $row++;
        $sheet->setCellValue("A{$row}", 'Export Date:');
        $sheet->setCellValue("B{$row}", now()->format('M d, Y H:i'));
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);

        // BOM Table Header
        $row += 2;
        $headerRow = $row;
        $headers = ['Code', 'Component', 'Quantity', 'UOM', 'Waste %', 'Actual Qty', 'Unit Cost', 'Total Cost'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue("{$col}{$row}", $header);
            $sheet->getStyle("{$col}{$row}")->getFont()->setBold(true);
            $sheet->getStyle("{$col}{$row}")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('4B5563');
            $sheet->getStyle("{$col}{$row}")->getFont()->getColor()->setRGB('FFFFFF');
            $col++;
        }

        // BOM Items
        $row++;
        foreach ($version->bomVersionItems as $item) {
            $sheet->setCellValue("A{$row}", $item->component->code);
            $sheet->setCellValue("B{$row}", $item->component->name);
            $sheet->setCellValue("C{$row}", $item->quantity);
            $sheet->setCellValue("D{$row}", $item->unit_of_measure);
            $sheet->setCellValue("E{$row}", $item->waste_factor);
            $sheet->setCellValue("F{$row}", $item->actual_quantity);
            $sheet->setCellValue("G{$row}", $item->unit_cost_snapshot / 100);
            $sheet->setCellValue("H{$row}", $item->total_cost_snapshot / 100);

            // Format currency
            $sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('$#,##0.00');

            $row++;
        }

        // Totals
        $row++;
        $sheet->setCellValue("G{$row}", 'BOM Material Cost:');
        $sheet->setCellValue("H{$row}", $version->bom_material_cost_snapshot / 100);
        $sheet->getStyle("G{$row}:H{$row}")->getFont()->setBold(true);
        $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('$#,##0.00');

        $row++;
        $sheet->setCellValue("G{$row}", 'Direct Labor:');
        $sheet->setCellValue("H{$row}", $version->direct_labor_cost_snapshot / 100);
        $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('$#,##0.00');

        $row++;
        $sheet->setCellValue("G{$row}", 'Direct Overhead:');
        $sheet->setCellValue("H{$row}", $version->direct_overhead_cost_snapshot / 100);
        $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('$#,##0.00');

        $row++;
        $sheet->setCellValue("G{$row}", 'Total Manufacturing Cost:');
        $sheet->setCellValue("H{$row}", $version->total_manufacturing_cost_snapshot / 100);
        $sheet->getStyle("G{$row}:H{$row}")->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('$#,##0.00');
        $sheet->getStyle("H{$row}")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('D1FAE5');

        // Auto-size columns
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Borders
        $lastRow = $row;
        $sheet->getStyle("A{$headerRow}:H{$lastRow}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        // Save file
        $filename = "BOM_{$product->sku}_{$version->version_display}_" . now()->format('Ymd_His') . ".xlsx";
        $path = storage_path("app/exports/bom/{$filename}");

        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return "exports/bom/{$filename}";
    }
}