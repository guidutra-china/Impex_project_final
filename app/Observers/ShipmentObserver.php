<?php

namespace App\Observers;

use App\Models\Shipment;
use Illuminate\Support\Facades\Log;

class ShipmentObserver
{
    /**
     * Handle the Shipment "created" event.
     */
    public function created(Shipment $shipment): void
    {
        Log::info('Shipment created', [
            'shipment_id' => $shipment->id,
            'shipment_number' => $shipment->shipment_number,
        ]);
    }

    /**
     * Handle the Shipment "updated" event.
     */
    public function updated(Shipment $shipment): void
    {
        // If status changed, log it
        if ($shipment->isDirty('status')) {
            Log::info('Shipment status changed', [
                'shipment_id' => $shipment->id,
                'old_status' => $shipment->getOriginal('status'),
                'new_status' => $shipment->status,
            ]);

            // Send notifications based on status
            $this->handleStatusChange($shipment);
        }

        // If confirmed, log it
        if ($shipment->isDirty('confirmed_at') && $shipment->confirmed_at) {
            Log::info('Shipment confirmed', [
                'shipment_id' => $shipment->id,
                'confirmed_by' => $shipment->confirmed_by,
                'confirmed_at' => $shipment->confirmed_at,
            ]);
        }
    }

    /**
     * Handle the Shipment "deleted" event.
     */
    public function deleted(Shipment $shipment): void
    {
        Log::info('Shipment deleted', [
            'shipment_id' => $shipment->id,
            'shipment_number' => $shipment->shipment_number,
        ]);
    }

    /**
     * Handle the Shipment "restored" event.
     */
    public function restored(Shipment $shipment): void
    {
        Log::info('Shipment restored', [
            'shipment_id' => $shipment->id,
            'shipment_number' => $shipment->shipment_number,
        ]);
    }

    /**
     * Handle the Shipment "force deleted" event.
     */
    public function forceDeleted(Shipment $shipment): void
    {
        Log::info('Shipment force deleted', [
            'shipment_id' => $shipment->id,
            'shipment_number' => $shipment->shipment_number,
        ]);
    }

    /**
     * Handle status change notifications
     */
    protected function handleStatusChange(Shipment $shipment): void
    {
        // TODO: Implement notifications
        // - Send email to customer when status is 'picked_up'
        // - Send email when status is 'delivered'
        // - Send internal notification when status is 'customs_clearance'
        
        switch ($shipment->status) {
            case 'confirmed':
                // Notify warehouse team
                break;
            
            case 'picked_up':
                // Notify customer
                break;
            
            case 'in_transit':
                // Update tracking
                break;
            
            case 'delivered':
                // Notify customer and sales team
                break;
            
            case 'cancelled':
                // Notify all stakeholders
                break;
        }
    }
}
