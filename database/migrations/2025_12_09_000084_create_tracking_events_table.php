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
        Schema::create('tracking_events', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('shipment_id');
            // TODO: `event_type` enum('created','picked_up','in_transit','customs_clearance','out_for_delivery','delivered','exception','returned') COLLATE utf8mb4_unicode_ci NOT NULL
            $table->string('event_description', 255);
            $table->text('notes');
            $table->string('location', 255)->nullable();
            $table->string('city', 255)->nullable();
            $table->string('country', 255)->nullable();
            $table->timestamp('event_date');
            // TODO: `source` enum('manual','carrier_api','system') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracking_events');
    }
};
