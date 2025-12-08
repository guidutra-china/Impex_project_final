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
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('customer_id')->nullable();
            $table->string('shipment_number', 50);
            $table->bigInteger('sales_order_id')->nullable();
            $table->bigInteger('purchase_order_id')->nullable();
            // TODO: `shipment_type` enum('outbound','inbound') COLLATE utf8mb4_unicode_ci DEFAULT 'outbound'
            $table->string('carrier', 255)->nullable();
            $table->string('tracking_number', 255)->nullable();
            $table->string('vessel_name', 255)->nullable();
            $table->string('voyage_number', 255)->nullable();
            $table->string('flight_number', 255)->nullable();
            // TODO: `shipping_method` enum('air','sea','land','courier') COLLATE utf8mb4_unicode_ci DEFAULT NULL
            // TODO: `status` enum('pending','preparing','ready_to_ship','picked_up','on_board','customs_clearance','out_for_delivery','delivered','cancelled','returned') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending'
            $table->text('origin_address');
            $table->string('origin_port', 255)->nullable();
            $table->text('destination_address');
            $table->string('destination_port', 255)->nullable();
            $table->string('final_destination', 255)->nullable();
            $table->text('notify_party_address');
            $table->date('shipment_date')->nullable();
            $table->date('estimated_departure_date')->nullable();
            $table->date('actual_departure_date')->nullable();
            $table->date('estimated_arrival_date')->nullable();
            $table->date('actual_arrival_date')->nullable();
            $table->date('estimated_delivery_date')->nullable();
            $table->date('actual_delivery_date')->nullable();
            $table->bigInteger('shipping_cost');
            $table->bigInteger('insurance_cost');
            $table->bigInteger('other_costs');
            $table->bigInteger('total_shipping_cost');
            $table->string('incoterm', 10)->nullable();
            $table->integer('commercial_invoice_generated');
            $table->integer('packing_list_generated');
            $table->string('bill_of_lading_number', 255)->nullable();
            $table->string('awb_number', 255)->nullable();
            $table->bigInteger('currency_id')->nullable();
            $table->decimal('total_weight', 10, 2)->nullable();
            $table->decimal('total_volume', 10, 2)->nullable();
            $table->integer('total_boxes');
            $table->integer('total_items');
            $table->integer('total_quantity');
            $table->text('notes');
            $table->text('special_instructions');
            $table->text('customs_notes');
            $table->timestamp('notification_sent_at')->nullable();
            $table->bigInteger('created_by')->nullable();
            $table->bigInteger('confirmed_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
