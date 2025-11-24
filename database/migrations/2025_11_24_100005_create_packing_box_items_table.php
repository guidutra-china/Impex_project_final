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
        Schema::create('packing_box_items', function (Blueprint $table) {
            $table->id();
            
            // === RELATIONSHIPS ===
            $table->foreignId('packing_box_id')->constrained('packing_boxes')->onDelete('cascade');
            $table->foreignId('shipment_item_id')->constrained('shipment_items')->onDelete('cascade');
            
            // === QUANTITY ===
            $table->integer('quantity')->comment('How many of this item in this box');
            
            // === NOTES ===
            $table->text('notes')->nullable();
            
            // === TIMESTAMPS ===
            $table->timestamps();
            
            // === INDEXES ===
            $table->index('packing_box_id', 'idx_packing_box');
            $table->index('shipment_item_id', 'idx_shipment_item');
            $table->unique(['packing_box_id', 'shipment_item_id'], 'idx_box_item_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packing_box_items');
    }
};
