<?php

namespace App\Services;

use App\Models\CommercialInvoice;
use App\Models\GeneratedDocument;
use App\Services\Export\PdfExportService;

class CommercialInvoicePdfService
{
    public function __construct(
        protected PdfExportService $pdfExportService
    ) {}

    /**
     * Generate Original PDF (with real values)
     */
    public function generateOriginalPdf(CommercialInvoice $invoice): GeneratedDocument
    {
        return $this->pdfExportService->generate(
            model: $invoice,
            documentType: 'commercial_invoice',
            view: 'pdf.invoices.commercial-invoice',
            data: [
                'invoice' => $invoice->load(['items', 'client', 'currency', 'paymentTerm', 'shipment']),
                'version' => 'original',
            ],
            options: [
                'paper' => 'a4',
                'orientation' => 'portrait',
                'notes' => 'Original Commercial Invoice - For payment and official records',
            ]
        );
    }

    /**
     * Generate Customs PDF (with customs discount applied)
     */
    public function generateCustomsPdf(CommercialInvoice $invoice): GeneratedDocument
    {
        if (!$invoice->hasCustomsDiscount()) {
            throw new \Exception('Cannot generate customs PDF: No customs discount configured');
        }

        return $this->pdfExportService->generate(
            model: $invoice,
            documentType: 'commercial_invoice_customs',
            view: 'pdf.invoices.commercial-invoice',
            data: [
                'invoice' => $invoice->load(['items', 'client', 'currency', 'paymentTerm', 'shipment']),
                'version' => 'customs',
            ],
            options: [
                'paper' => 'a4',
                'orientation' => 'portrait',
                'notes' => "Customs Commercial Invoice - {$invoice->customs_discount_percentage}% discount applied for customs purposes only",
            ]
        );
    }

    /**
     * Generate both versions (Original + Customs)
     */
    public function generateBothVersions(CommercialInvoice $invoice): array
    {
        $documents = [];

        // Always generate original
        $documents['original'] = $this->generateOriginalPdf($invoice);

        // Generate customs if discount is configured
        if ($invoice->hasCustomsDiscount()) {
            $documents['customs'] = $this->generateCustomsPdf($invoice);
        }

        return $documents;
    }
}
