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
        Schema::create('warehouse_stock', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('restrict');
            $table->foreignId('warehouse_location_id')->nullable()->constrained('warehouse_locations')->onDelete('set null');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            
            // === QUANTIDADE ===
            $table->integer('quantity')->default(0);
            
            // === CUSTO ===
            $table->bigInteger('unit_cost')->default(0)->comment('In cents');
            $table->bigInteger('total_value')->default(0)->comment('In cents');
            
            // === DATAS ===
            $table->date('last_movement_date')->nullable();
            
            $table->timestamp('updated_at')->nullable();
            
            // === INDEXES ===
            $table->unique(['warehouse_id', 'warehouse_location_id', 'product_id'], 'unique_stock');
            $table->index('warehouse_id');
            $table->index('product_id');
            $table->index('warehouse_location_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_stock');
    }
};
