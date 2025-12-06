<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\GeneratedDocument;
use App\Services\Export\PdfExportService;

class CommercialInvoicePdfService
{
    public function __construct(
        protected PdfExportService $pdfExportService
    ) {}

    /**
     * Generate PDF from Shipment (accepts 'original' or 'customs' version)
     */
    public function generate(Shipment $shipment, string $version = 'original'): string
    {
        // Validate version
        if (!in_array($version, ['original', 'customs'])) {
            throw new \Exception('Invalid version. Must be "original" or "customs"');
        }

        // Check if customs version is requested but no discount configured
        if ($version === 'customs' && !$this->hasCustomsDiscount($shipment)) {
            throw new \Exception('Cannot generate customs PDF: No customs discount configured');
        }

        $documentType = $version === 'customs' ? 'commercial_invoice_customs' : 'commercial_invoice';
        
        $generatedDocument = $this->pdfExportService->generate(
            model: $shipment,
            documentType: $documentType,
            view: 'pdf.invoices.commercial-invoice',
            data: [
                'shipment' => $shipment->load([
                    'customer',
                    'containers.items.product',
                    'proformaInvoices.currency',
                    'proformaInvoices.paymentTerm',
                    'commercialInvoice',
                ]),
                'version' => $version,
            ],
            options: [
                'paper' => 'a4',
                'orientation' => 'portrait',
                'notes' => $version === 'customs' 
                    ? "Customs Commercial Invoice - {$this->getCustomsDiscount($shipment)}% discount applied"
                    : 'Original Commercial Invoice - For payment and official records',
            ]
        );

        return $generatedDocument->file_path;
    }

    /**
     * Generate Original PDF (with real values)
     */
    public function generateOriginalPdf(Shipment $shipment): string
    {
        return $this->generate($shipment, 'original');
    }

    /**
     * Generate Customs PDF (with customs discount applied)
     */
    public function generateCustomsPdf(Shipment $shipment): string
    {
        return $this->generate($shipment, 'customs');
    }

    /**
     * Generate both versions (Original + Customs)
     */
    public function generateBothVersions(Shipment $shipment): array
    {
        $paths = [];

        // Always generate original
        $paths['original'] = $this->generateOriginalPdf($shipment);

        // Generate customs if discount is configured
        if ($this->hasCustomsDiscount($shipment)) {
            $paths['customs'] = $this->generateCustomsPdf($shipment);
        }

        return $paths;
    }

    /**
     * Check if shipment has customs discount configured
     */
    protected function hasCustomsDiscount(Shipment $shipment): bool
    {
        $commercialInvoice = $shipment->commercialInvoice;
        return $commercialInvoice && $commercialInvoice->customs_discount_percentage > 0;
    }

    /**
     * Get customs discount percentage
     */
    protected function getCustomsDiscount(Shipment $shipment): float
    {
        return $shipment->commercialInvoice?->customs_discount_percentage ?? 0;
    }
}
