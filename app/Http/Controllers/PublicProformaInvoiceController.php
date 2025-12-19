<?php

namespace App\Http\Controllers;

use App\Models\ProformaInvoice;
use Barryvdh\DomPDF\Facade\Pdf;

class PublicProformaInvoiceController extends Controller
{
    /**
     * Show public proforma invoice PDF (no login required)
     */
    public function show(string $token)
    {
        // Find proforma invoice by public token
        $proformaInvoice = ProformaInvoice::where('public_token', $token)
            ->with([
                'customer',
                'order',
                'customerQuote',
                'items.product',
                'items.supplierQuote.supplier',
                'currency',
            ])
            ->firstOrFail();

        // Track view if not already viewed
        if (!$proformaInvoice->viewed_at) {
            $proformaInvoice->update([
                'viewed_at' => now(),
            ]);
        }

        // Generate PDF
        $pdf = Pdf::loadView('pdf.proforma-invoice', [
            'proformaInvoice' => $proformaInvoice,
            'isDraft' => $proformaInvoice->status === 'draft',
        ]);

        // Add watermark if draft
        if ($proformaInvoice->status === 'draft') {
            $pdf->setOption('watermark', 'DRAFT');
            $pdf->setOption('show_watermark', true);
        }

        return $pdf->stream('Proforma_Invoice_' . $proformaInvoice->proforma_number . '.pdf');
    }

    /**
     * Download proforma invoice PDF
     */
    public function download(string $token)
    {
        // Find proforma invoice by public token
        $proformaInvoice = ProformaInvoice::where('public_token', $token)
            ->with([
                'customer',
                'order',
                'customerQuote',
                'items.product',
                'items.supplierQuote.supplier',
                'currency',
            ])
            ->firstOrFail();

        // Generate PDF
        $pdf = Pdf::loadView('pdf.proforma-invoice', [
            'proformaInvoice' => $proformaInvoice,
            'isDraft' => $proformaInvoice->status === 'draft',
        ]);

        // Add watermark if draft
        if ($proformaInvoice->status === 'draft') {
            $pdf->setOption('watermark', 'DRAFT');
            $pdf->setOption('show_watermark', true);
        }

        return $pdf->download('Proforma_Invoice_' . $proformaInvoice->proforma_number . '.pdf');
    }
}
