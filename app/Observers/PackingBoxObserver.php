<?php

namespace App\Observers;

use App\Models\PackingBox;
use Illuminate\Support\Facades\Log;

class PackingBoxObserver
{
    /**
     * Handle the PackingBox "created" event.
     */
    public function created(PackingBox $box): void
    {
        // Recalculate shipment totals
        $box->shipment->calculateTotals();

        Log::info('Packing box created', [
            'box_id' => $box->id,
            'shipment_id' => $box->shipment_id,
            'box_number' => $box->box_number,
        ]);
    }

    /**
     * Handle the PackingBox "updated" event.
     */
    public function updated(PackingBox $box): void
    {
        // If sealed status changed, log it
        if ($box->isDirty('packing_status')) {
            Log::info('Packing box status changed', [
                'box_id' => $box->id,
                'old_status' => $box->getOriginal('packing_status'),
                'new_status' => $box->packing_status,
            ]);

            // If sealed, log who and when
            if ($box->packing_status === 'sealed') {
                Log::info('Packing box sealed', [
                    'box_id' => $box->id,
                    'sealed_by' => $box->sealed_by,
                    'sealed_at' => $box->sealed_at,
                    'total_items' => $box->total_items,
                    'total_quantity' => $box->total_quantity,
                ]);
            }
        }

        // Recalculate shipment totals if dimensions or weight changed
        if ($box->isDirty(['length', 'width', 'height', 'gross_weight', 'net_weight'])) {
            $box->shipment->calculateTotals();
        }
    }

    /**
     * Handle the PackingBox "deleted" event.
     */
    public function deleted(PackingBox $box): void
    {
        // Recalculate shipment totals
        if ($box->shipment) {
            $box->shipment->calculateTotals();
        }

        Log::info('Packing box deleted', [
            'box_id' => $box->id,
            'shipment_id' => $box->shipment_id,
            'box_number' => $box->box_number,
        ]);
    }
}
