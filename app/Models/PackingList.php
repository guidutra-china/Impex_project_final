<?php

namespace App\Models;

use App\Models\Scopes\ClientOwnershipScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackingList extends Model
{
    use HasFactory;

    protected $table = 'packing_lists';

    protected static function booted(): void
    {
        static::addGlobalScope(new ClientOwnershipScope());
        
        // Auto-generate packing list number on creation
        static::creating(function (PackingList $packingList) {
            if (empty($packingList->packing_list_number)) {
                $packingList->packing_list_number = static::generatePackingListNumber($packingList->client_id);
            }
        });
    }

    protected $fillable = [
        'client_id',
        'shipment_id',
        'packing_list_number',
        'packing_date',
        
        // Exporter details
        'exporter_name',
        'exporter_address',
        'exporter_tax_id',
        'exporter_country',
        
        // Importer details
        'importer_name',
        'importer_address',
        'importer_tax_id',
        'importer_country',
        
        // Shipping details
        'port_of_loading',
        'port_of_discharge',
        'final_destination',
        'bl_number',
        'container_numbers',
        
        // Additional info
        'notes',
        'display_options',
    ];

    protected $casts = [
        'packing_date' => 'date',
        'display_options' => 'array',
    ];

    // Relationships
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * Generate Packing List number in format: PL-YY-NNNN
     */
    public static function generatePackingListNumber(?int $clientId = null): string
    {
        $year = now()->format('y'); // 2-digit year
        
        // Get the last packing list number for this year
        $query = static::query()
            ->where('packing_list_number', 'LIKE', "PL-{$year}-%")
            ->orderByDesc('packing_list_number');
        
        if ($clientId) {
            $query->where('client_id', $clientId);
        }
        
        $lastPackingList = $query->first();
        
        if ($lastPackingList) {
            // Extract the sequential number from the last PL number
            $lastNumber = (int) substr($lastPackingList->packing_list_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        
        // Format: PL-YY-NNNN (e.g., PL-25-0001)
        return sprintf('PL-%s-%04d', $year, $nextNumber);
    }

    /**
     * Generate Packing List from Shipment
     * Auto-fills all data including Exporter, Importer, Shipping Details
     */
    public static function generateFromShipment(Shipment $shipment, array $additionalData = []): self
    {
        $packingList = new self();
        
        // Basic info
        $packingList->shipment_id = $shipment->id;
        $packingList->client_id = $shipment->customer_id;
        $packingList->packing_date = now();
        
        // Shipping details from shipment
        $packingList->port_of_loading = $shipment->origin_port;
        $packingList->port_of_discharge = $shipment->destination_port;
        $packingList->final_destination = $shipment->destination_address;
        $packingList->bl_number = $shipment->bl_number ?? '';
        
        // Container numbers from shipment
        $containerNumbers = $shipment->containers()->pluck('container_number')->join(', ');
        $packingList->container_numbers = $containerNumbers;
        
        // Exporter details from Company Settings
        $companySettings = CompanySetting::current();
        if ($companySettings) {
            $packingList->exporter_name = $companySettings->company_name;
            $packingList->exporter_address = $companySettings->full_address;
            $packingList->exporter_tax_id = $companySettings->tax_id;
            $packingList->exporter_country = $companySettings->country;
        }
        
        // Importer details from Customer
        $customer = $shipment->customer;
        if ($customer) {
            $packingList->importer_name = $customer->name;
            // Build full address from customer fields
            $addressParts = array_filter([
                $customer->address,
                $customer->city,
                $customer->state . ' ' . $customer->zip,
                $customer->country,
            ]);
            $packingList->importer_address = implode(', ', $addressParts);
            $packingList->importer_tax_id = $customer->tax_id;
            $packingList->importer_country = $customer->country;
        }
        
        // Default display options
        $packingList->display_options = [
            'show_exporter_details' => true,
            'show_importer_details' => true,
            'show_shipping_details' => true,
            'show_supplier_code' => false,
            'show_hs_codes' => true,
            'show_country_of_origin' => true,
            'show_weight_volume' => true, // Important for packing list
        ];
        
        // Merge additional data
        $packingList->fill($additionalData);
        
        $packingList->save();
        
        return $packingList;
    }
}
