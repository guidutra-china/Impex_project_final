<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            // === VESSEL/FLIGHT INFO ===
            $table->string('vessel_name')->nullable()->after('container_number')->comment('For sea freight');
            $table->string('voyage_number')->nullable()->after('vessel_name')->comment('For sea freight');
            $table->string('flight_number')->nullable()->after('voyage_number')->comment('For air freight');
            
            // === PORTS ===
            $table->string('origin_port')->nullable()->after('origin_address')->comment('Port of loading');
            $table->string('destination_port')->nullable()->after('destination_address')->comment('Port of discharge');
            $table->text('notify_party_address')->nullable()->after('destination_port');
            
            // === DATES (Enhanced) ===
            $table->date('estimated_departure_date')->nullable()->after('shipment_date');
            $table->date('actual_departure_date')->nullable()->after('estimated_departure_date');
            $table->date('estimated_arrival_date')->nullable()->after('actual_departure_date');
            $table->date('actual_arrival_date')->nullable()->after('estimated_arrival_date');
            
            // === TOTALS (Calculated) ===
            $table->integer('total_boxes')->default(0)->after('total_volume')->comment('Count of packing boxes');
            $table->integer('total_items')->default(0)->after('total_boxes')->comment('Count of different items');
            $table->integer('total_quantity')->default(0)->after('total_items')->comment('Sum of all quantities');
            
            // === COSTS (Enhanced) ===
            $table->bigInteger('insurance_cost')->default(0)->after('shipping_cost')->comment('In cents');
            $table->bigInteger('other_costs')->default(0)->after('insurance_cost')->comment('In cents');
            $table->bigInteger('total_shipping_cost')->default(0)->after('other_costs')->comment('Sum of all costs in cents');
            
            // === INCOTERM ===
            $table->string('incoterm', 10)->nullable()->after('total_shipping_cost')->comment('FOB, CIF, EXW, DDP, etc.');
            
            // === DOCUMENTS ===
            $table->boolean('commercial_invoice_generated')->default(false)->after('incoterm');
            $table->boolean('packing_list_generated')->default(false)->after('commercial_invoice_generated');
            $table->string('bill_of_lading_number')->nullable()->after('packing_list_generated');
            $table->string('awb_number')->nullable()->after('bill_of_lading_number')->comment('Air Waybill number');
            
            // === CUSTOMS ===
            $table->text('customs_notes')->nullable()->after('special_instructions');
            
            // === CONFIRMATION ===
            $table->foreignId('confirmed_by')->nullable()->after('created_by')->constrained('users')->onDelete('set null');
            $table->timestamp('confirmed_at')->nullable()->after('confirmed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropForeign(['confirmed_by']);
            
            $table->dropColumn([
                'vessel_name',
                'voyage_number',
                'flight_number',
                'origin_port',
                'destination_port',
                'notify_party_address',
                'estimated_departure_date',
                'actual_departure_date',
                'estimated_arrival_date',
                'actual_arrival_date',
                'total_boxes',
                'total_items',
                'total_quantity',
                'insurance_cost',
                'other_costs',
                'total_shipping_cost',
                'incoterm',
                'commercial_invoice_generated',
                'packing_list_generated',
                'bill_of_lading_number',
                'awb_number',
                'customs_notes',
                'confirmed_by',
                'confirmed_at',
            ]);
        });
    }
};
