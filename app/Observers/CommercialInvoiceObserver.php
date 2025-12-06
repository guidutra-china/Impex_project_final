<?php

namespace App\Observers;

use App\Models\CommercialInvoice;

class CommercialInvoiceObserver
{
    public function creating(CommercialInvoice $commercialInvoice): void
    {
        // Auto-generate invoice number if not set
        if (empty($commercialInvoice->invoice_number)) {
            $commercialInvoice->invoice_number = CommercialInvoice::generateInvoiceNumber();
        }
    }

    public function created(CommercialInvoice $commercialInvoice): void
    {
        // Commercial Invoice totals are calculated from items, not stored separately
        // No need to recalculate here
    }

    public function updated(CommercialInvoice $commercialInvoice): void
    {
        // Recalculate totals if items changed
        if ($commercialInvoice->wasChanged(['subtotal', 'tax', 'total'])) {
            // Already updated, no need to recalculate
        }
    }
}
