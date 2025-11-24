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
        Schema::create('packing_boxes', function (Blueprint $table) {
            $table->id();
            
            // === RELATIONSHIP ===
            $table->foreignId('shipment_id')->constrained('shipments')->onDelete('cascade');
            
            // === BOX IDENTIFICATION ===
            $table->integer('box_number')->comment('Sequential number: 1, 2, 3...');
            $table->string('box_label')->nullable()->comment('Custom label like BOX-001');
            $table->enum('box_type', ['carton', 'pallet', 'crate', 'bag', 'drum', 'other'])->default('carton');
            
            // === DIMENSIONS (in cm) ===
            $table->decimal('length', 10, 2)->nullable()->comment('Length in cm');
            $table->decimal('width', 10, 2)->nullable()->comment('Width in cm');
            $table->decimal('height', 10, 2)->nullable()->comment('Height in cm');
            
            // === WEIGHT & VOLUME ===
            $table->decimal('gross_weight', 10, 2)->nullable()->comment('Including box weight in kg');
            $table->decimal('net_weight', 10, 2)->nullable()->comment('Product weight only in kg');
            $table->decimal('volume', 10, 4)->nullable()->comment('Calculated volume in m³ (CBM)');
            
            // === TOTALS ===
            $table->integer('total_items')->default(0)->comment('Count of different items');
            $table->integer('total_quantity')->default(0)->comment('Sum of all quantities');
            
            // === STATUS ===
            $table->enum('packing_status', ['empty', 'packing', 'sealed', 'shipped'])->default('empty');
            $table->timestamp('sealed_at')->nullable();
            $table->foreignId('sealed_by')->nullable()->constrained('users')->onDelete('set null');
            
            // === NOTES ===
            $table->text('notes')->nullable();
            $table->string('contents_description')->nullable()->comment('Brief description of contents');
            
            // === TIMESTAMPS ===
            $table->timestamps();
            
            // === INDEXES ===
            $table->index(['shipment_id', 'box_number'], 'idx_shipment_box');
            $table->index('packing_status', 'idx_packing_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packing_boxes');
    }
};
