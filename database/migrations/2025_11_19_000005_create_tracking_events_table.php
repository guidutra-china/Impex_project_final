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
            
            $table->foreignId('shipment_id')->constrained('shipments')->onDelete('cascade');
            
            // === EVENTO ===
            $table->enum('event_type', [
                'created',
                'picked_up',
                'in_transit',
                'customs_clearance',
                'out_for_delivery',
                'delivered',
                'exception',
                'returned'
            ]);
            
            // === DESCRIÇÃO ===
            $table->string('event_description');
            $table->text('notes')->nullable();
            
            // === LOCALIZAÇÃO ===
            $table->string('location')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            
            // === DATA/HORA ===
            $table->timestamp('event_date');
            
            // === FONTE ===
            $table->enum('source', ['manual', 'carrier_api', 'system'])->default('manual');
            
            $table->timestamp('created_at');
            
            // === INDEXES ===
            $table->index('shipment_id');
            $table->index('event_date');
            $table->index(['shipment_id', 'event_date'], 'idx_shipment_event_date');
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
