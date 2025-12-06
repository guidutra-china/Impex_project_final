<?php

namespace App\Filament\Resources\Shipments\Pages;

use App\Filament\Resources\Shipments\ShipmentResource;
use App\Models\CommercialInvoice;
use Filament\Resources\Pages\CreateRecord;

class CreateShipment extends CreateRecord
{
    protected static string $resource = ShipmentResource::class;

    /**
     * Handle saving of commercialInvoice relationship data
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Extract commercialInvoice data before creating Shipment
        if (isset($data['commercialInvoice'])) {
            $this->commercialInvoiceData = $data['commercialInvoice'];
            unset($data['commercialInvoice']);
        }
        
        return $data;
    }

    /**
     * Save commercialInvoice after Shipment is created
     */
    protected function afterCreate(): void
    {
        if (isset($this->commercialInvoiceData)) {
            // Create CommercialInvoice
            $commercialInvoice = new CommercialInvoice();
            $commercialInvoice->shipment_id = $this->record->id;
            
            // Fill data
            $commercialInvoice->fill($this->commercialInvoiceData);
            
            // Ensure invoice_number is generated if not exists
            if (!$commercialInvoice->invoice_number) {
                $commercialInvoice->invoice_number = CommercialInvoice::generateInvoiceNumber();
            }
            
            $commercialInvoice->save();
        }
    }

    protected array $commercialInvoiceData = [];
}
