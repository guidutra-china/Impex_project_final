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
                'paymentTerm',
            ])
            ->firstOrFail();

        // Track view if not already viewed
        if (!$proformaInvoice->viewed_at) {
            $proformaInvoice->update([
                'viewed_at' => now(),
            ]);
        }

        // Generate PDF
        $pdf = Pdf::loadView('pdf.proforma-invoice.template', [
            'model' => $proformaInvoice,
            'generated_at' => now(),
            'isDraft' => $proformaInvoice->status === 'draft',
        ]);

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
                'paymentTerm',
            ])
            ->firstOrFail();

        // Generate PDF
        $pdf = Pdf::loadView('pdf.proforma-invoice.template', [
            'model' => $proformaInvoice,
            'generated_at' => now(),
            'isDraft' => $proformaInvoice->status === 'draft',
        ]);

        return $pdf->download('Proforma_Invoice_' . $proformaInvoice->proforma_number . '.pdf');
    }
}
