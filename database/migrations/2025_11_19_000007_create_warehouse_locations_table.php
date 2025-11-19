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
        Schema::create('warehouse_locations', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            
            // === IDENTIFICAÇÃO ===
            $table->string('location_code', 50)->comment('Ex: A-01-01 (Aisle-Rack-Shelf)');
            $table->string('location_name')->nullable();
            
            // === TIPO ===
            $table->enum('location_type', ['shelf', 'pallet', 'bin', 'floor'])->default('shelf');
            
            // === CAPACIDADE ===
            $table->decimal('capacity', 10, 2)->nullable()->comment('Capacity (m³)');
            
            // === STATUS ===
            $table->boolean('is_available')->default(true);
            
            $table->timestamps();
            
            // === INDEXES ===
            $table->unique(['warehouse_id', 'location_code'], 'unique_location');
            $table->index('warehouse_id');
            $table->index('is_available');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_locations');
    }
};
