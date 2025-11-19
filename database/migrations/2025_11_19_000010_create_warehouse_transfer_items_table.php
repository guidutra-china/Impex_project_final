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
        Schema::create('warehouse_transfer_items', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('warehouse_transfer_id')->constrained('warehouse_transfers')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            
            // === QUANTIDADE ===
            $table->integer('quantity');
            
            // === CUSTO ===
            $table->bigInteger('unit_cost')->comment('In cents');
            
            $table->timestamp('created_at');
            
            // === INDEXES ===
            $table->index('warehouse_transfer_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_transfer_items');
    }
};
