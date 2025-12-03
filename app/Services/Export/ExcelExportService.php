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
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use App\Models\CompanySetting;

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
            'rfq' => $this->generateRfq($sheet, $model),
            'supplier_quote' => $this->generateSupplierQuote($sheet, $model),
            'purchase_order' => $this->generatePurchaseOrder($sheet, $model),
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
        $fullPath = storage_path('app/' . $filePath);
        
        // Ensure the full directory path exists (including nested directories)
        $dirPath = dirname($fullPath);
        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0755, true);
        }
        
        $writer->save($fullPath);
        
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
     * Add company header with logo to Excel sheet
     */
    protected function addCompanyHeader($sheet, string $documentTitle, int &$row): void
    {
        $companySettings = CompanySetting::current();
        
        // Add logo if available
        if ($companySettings && $companySettings->logo_full_path && file_exists($companySettings->logo_full_path)) {
            $drawing = new Drawing();
            $drawing->setName('Company Logo');
            $drawing->setDescription('Company Logo');
            $drawing->setPath($companySettings->logo_full_path);
            $drawing->setHeight(60);
            $drawing->setCoordinates('A' . $row);
            $drawing->setWorksheet($sheet);
        }
        
        // Company info on the left (below logo)
        $infoRow = $row + 4;
        if ($companySettings) {
            $sheet->setCellValue('A' . $infoRow, $companySettings->company_name ?? config('app.name'));
            $sheet->getStyle('A' . $infoRow)->getFont()->setBold(true)->setSize(12);
            $infoRow++;
            
            if ($companySettings->address) {
                $sheet->setCellValue('A' . $infoRow, $companySettings->address);
                $infoRow++;
            }
            
            $cityLine = '';
            if ($companySettings->city) $cityLine .= $companySettings->city;
            if ($companySettings->state) $cityLine .= ($cityLine ? ', ' : '') . $companySettings->state;
            if ($companySettings->postal_code) $cityLine .= ($cityLine ? ' ' : '') . $companySettings->postal_code;
            if ($cityLine) {
                $sheet->setCellValue('A' . $infoRow, $cityLine);
                $infoRow++;
            }
            
            if ($companySettings->country) {
                $sheet->setCellValue('A' . $infoRow, $companySettings->country);
                $infoRow++;
            }
            
            if ($companySettings->email) {
                $sheet->setCellValue('A' . $infoRow, 'Email: ' . $companySettings->email);
                $infoRow++;
            }
            
            if ($companySettings->phone) {
                $sheet->setCellValue('A' . $infoRow, 'Phone: ' . $companySettings->phone);
                $infoRow++;
            }
        }
        
        // Document title on the right
        $titleRow = $row;
        $sheet->setCellValue('F' . $titleRow, $documentTitle);
        $sheet->mergeCells('F' . $titleRow . ':G' . $titleRow);
        $sheet->getStyle('F' . $titleRow)->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('F' . $titleRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        
        // Move row pointer past the header
        $row = max($infoRow, $titleRow + 2) + 1;
    }

    /**
     * Generate Proforma Invoice Excel
     */
    protected function generateProformaInvoice($sheet, $model): void
    {
        $row = 1;
        
        // Add company header with logo
        $this->addCompanyHeader($sheet, 'PROFORMA INVOICE', $row);
        $row++;
        
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
        $row = 1;
        
        // Add company header with logo
        $this->addCompanyHeader($sheet, 'COMMERCIAL INVOICE', $row);
        $row++;
        
        // Invoice Info
        $sheet->setCellValue('A' . $row, 'Invoice Number:');
        $sheet->setCellValue('B' . $row, $model->invoice_number);
        $sheet->setCellValue('E' . $row, 'Invoice Date:');
        $sheet->setCellValue('F' . $row, $model->invoice_date ? $model->invoice_date->format('Y-m-d') : date('Y-m-d'));
        $row++;
        
        $sheet->setCellValue('A' . $row, 'Revision:');
        $sheet->setCellValue('B' . $row, $model->revision_number ?? 1);
        if ($model->shipment_date) {
            $sheet->setCellValue('E' . $row, 'Shipment Date:');
            $sheet->setCellValue('F' . $row, $model->shipment_date->format('Y-m-d'));
        }
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
        
        if ($model->client) {
            $sheet->setCellValue('A' . $row, $model->client->name);
            $row++;
            if ($model->client->address) {
                $sheet->setCellValue('A' . $row, $model->client->address);
                $row++;
            }
        }
        $row++;
        
        // Items Header
        $headers = ['#', 'Code', 'Description', 'Quantity', 'Unit Price', 'Total'];
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
            $sheet->setCellValue('B' . $row, $item->product_code ?? 'N/A');
            $sheet->setCellValue('C' . $row, $item->product_name ?? $item->description);
            $sheet->setCellValue('D' . $row, $item->quantity);
            $sheet->setCellValue('E' . $row, $item->unit_price);
            $sheet->setCellValue('F' . $row, $item->total_price);
            
            // Format currency
            $sheet->getStyle('E' . $row)->getNumberFormat()
                ->setFormatCode('#,##0.00');
            $sheet->getStyle('F' . $row)->getNumberFormat()
                ->setFormatCode('#,##0.00');
            
            $row++;
        }
        $itemEndRow = $row - 1;
        
        // Totals
        $row++;
        $sheet->setCellValue('E' . $row, 'Subtotal:');
        $sheet->setCellValue('F' . $row, $model->subtotal);
        $sheet->getStyle('E' . $row)->getFont()->setBold(true);
        $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $row++;
        
        if ($model->commission > 0) {
            $sheet->setCellValue('E' . $row, 'Commission:');
            $sheet->setCellValue('F' . $row, $model->commission);
            $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $row++;
        }
        
        if ($model->tax > 0) {
            $sheet->setCellValue('E' . $row, 'Tax:');
            $sheet->setCellValue('F' . $row, $model->tax);
            $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $row++;
        }
        
        $sheet->setCellValue('E' . $row, 'TOTAL:');
        $sheet->setCellValue('F' . $row, $model->total);
        $sheet->getStyle('E' . $row . ':F' . $row)->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('E' . $row . ':F' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFF3F4F6');
        
        // Borders
        $sheet->getStyle('A' . $headerRow . ':F' . $itemEndRow)->getBorders()
            ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        // Column widths
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(12);
        $sheet->getColumnDimension('C')->setWidth(40);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(15);
    }

    /**
     * Generate RFQ Excel
     */
    protected function generateRfq($sheet, $model): void
    {
        $row = 1;
        
        // Add company header with logo
        $this->addCompanyHeader($sheet, 'REQUEST FOR QUOTATION', $row);
        $row++;
        
        // RFQ Info
        $sheet->setCellValue('A' . $row, 'RFQ Number:');
        $sheet->setCellValue('B' . $row, $model->order_number);
        $sheet->setCellValue('E' . $row, 'Date:');
        $sheet->setCellValue('F' . $row, $model->created_at->format('Y-m-d'));
        $row++;
        
        $sheet->setCellValue('A' . $row, 'Status:');
        $sheet->setCellValue('B' . $row, strtoupper($model->status));
        $sheet->setCellValue('E' . $row, 'Currency:');
        $sheet->setCellValue('F' . $row, $model->currency->code ?? 'USD');
        $row += 2;
        
        // Customer Info
        $sheet->setCellValue('A' . $row, 'CUSTOMER:');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        
        if ($model->customer) {
            $sheet->setCellValue('A' . $row, $model->customer->name);
            $row++;
            if ($model->customer->address) {
                $sheet->setCellValue('A' . $row, $model->customer->address);
                $row++;
            }
        }
        $row++;
        
        // Items Header
        $headers = ['#', 'Product', 'Quantity', 'Target Price', 'Commission %', 'Total'];
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
            $sheet->setCellValue('B' . $row, $item->product->name ?? 'N/A');
            $sheet->setCellValue('C' . $row, $item->quantity);
            $sheet->setCellValue('D' . $row, $item->requested_unit_price);
            $sheet->setCellValue('E' . $row, $item->commission_percent ? $item->commission_percent . '%' : '-');
            $sheet->setCellValue('F' . $row, $item->requested_unit_price * $item->quantity);
            
            // Format currency
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            
            $row++;
        }
        $itemEndRow = $row - 1;
        
        // Borders
        $sheet->getStyle('A' . $headerRow . ':F' . $itemEndRow)->getBorders()
            ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        // Column widths
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(15);
    }

    /**
     * Generate Supplier Quote Excel
     */
    protected function generateSupplierQuote($sheet, $model): void
    {
        $row = 1;
        
        // Add company header with logo
        $this->addCompanyHeader($sheet, 'SUPPLIER QUOTATION', $row);
        $row++;
        
        // Quote Info
        $sheet->setCellValue('A' . $row, 'Quote Number:');
        $sheet->setCellValue('B' . $row, $model->quote_number);
        $sheet->setCellValue('E' . $row, 'Date:');
        $sheet->setCellValue('F' . $row, $model->created_at->format('Y-m-d'));
        $row++;
        
        $sheet->setCellValue('A' . $row, 'Status:');
        $sheet->setCellValue('B' . $row, strtoupper($model->status));
        $sheet->setCellValue('E' . $row, 'Currency:');
        $sheet->setCellValue('F' . $row, $model->currency->code ?? 'USD');
        $row++;
        
        $sheet->setCellValue('A' . $row, 'Supplier:');
        $sheet->setCellValue('B' . $row, $model->supplier->name ?? 'N/A');
        $row += 2;
        
        // Items Header
        $headers = ['#', 'Product', 'Quantity', 'Unit Price', 'Total'];
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
            $sheet->setCellValue('B' . $row, $item->product->name ?? 'N/A');
            $sheet->setCellValue('C' . $row, $item->quantity);
            $sheet->setCellValue('D' . $row, $item->unit_price_after_dollars);
            $sheet->setCellValue('E' . $row, $item->total_price_after_dollars);
            
            // Format currency
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            
            $row++;
        }
        $itemEndRow = $row - 1;
        
        // Totals
        $row++;
        $sheet->setCellValue('D' . $row, 'Subtotal:');
        $sheet->setCellValue('E' . $row, $model->subtotal);
        $sheet->getStyle('D' . $row)->getFont()->setBold(true);
        $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $row++;
        
        $sheet->setCellValue('D' . $row, 'TOTAL:');
        $sheet->setCellValue('E' . $row, $model->total);
        $sheet->getStyle('D' . $row . ':E' . $row)->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('D' . $row . ':E' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFF3F4F6');
        
        // Borders
        $sheet->getStyle('A' . $headerRow . ':E' . $itemEndRow)->getBorders()
            ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        // Column widths
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(15);
    }

    /**
     * Generate Purchase Order Excel
     */
    protected function generatePurchaseOrder($sheet, $model): void
    {
        $row = 1;
        
        // Add company header with logo
        $this->addCompanyHeader($sheet, 'PURCHASE ORDER', $row);
        $row++;
        
        // PO Info
        $sheet->setCellValue('A' . $row, 'PO Number:');
        $sheet->setCellValue('B' . $row, $model->po_number);
        $sheet->setCellValue('E' . $row, 'Date:');
        $sheet->setCellValue('F' . $row, $model->po_date ? $model->po_date->format('Y-m-d') : $model->created_at->format('Y-m-d'));
        $row++;
        
        $sheet->setCellValue('A' . $row, 'Status:');
        $sheet->setCellValue('B' . $row, strtoupper($model->status));
        $sheet->setCellValue('E' . $row, 'Currency:');
        $sheet->setCellValue('F' . $row, $model->currency->code ?? 'USD');
        $row++;
        
        $sheet->setCellValue('A' . $row, 'Supplier:');
        $sheet->setCellValue('B' . $row, $model->supplier->name ?? 'N/A');
        $row += 2;
        
        // Items Header
        $headers = ['#', 'Product', 'Quantity', 'Unit Cost', 'Total'];
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
            $sheet->setCellValue('B' . $row, $item->product->name ?? $item->product_name ?? 'N/A');
            $sheet->setCellValue('C' . $row, $item->quantity);
            $sheet->setCellValue('D' . $row, $item->unit_cost);
            $sheet->setCellValue('E' . $row, $item->total_cost);
            
            // Format currency
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            
            $row++;
        }
        $itemEndRow = $row - 1;
        
        // Totals
        $row++;
        $sheet->setCellValue('D' . $row, 'Subtotal:');
        $sheet->setCellValue('E' . $row, $model->subtotal);
        $sheet->getStyle('D' . $row)->getFont()->setBold(true);
        $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $row++;
        
        if ($model->tax > 0) {
            $sheet->setCellValue('D' . $row, 'Tax:');
            $sheet->setCellValue('E' . $row, $model->tax);
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $row++;
        }
        
        $sheet->setCellValue('D' . $row, 'TOTAL:');
        $sheet->setCellValue('E' . $row, $model->total);
        $sheet->getStyle('D' . $row . ':E' . $row)->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('D' . $row . ':E' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFF3F4F6');
        
        // Borders
        $sheet->getStyle('A' . $headerRow . ':E' . $itemEndRow)->getBorders()
            ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        // Column widths
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(15);
    }

    /**
     * Get document number from model
     */
    protected function getDocumentNumber(Model $model, string $documentType): ?string
    {
        return match ($documentType) {
            'rfq' => $model->order_number ?? null,
            'supplier_quote' => $model->quote_number ?? null,
            'purchase_order' => $model->po_number ?? null,
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
