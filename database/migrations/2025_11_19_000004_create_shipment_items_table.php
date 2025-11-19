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
        Schema::create('shipment_items', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('shipment_id')->constrained('shipments')->onDelete('cascade');
            $table->foreignId('sales_order_item_id')->nullable()->constrained('order_items')->onDelete('set null');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            
            // === QUANTIDADE ===
            $table->integer('quantity');
            
            // === SNAPSHOT (não muda se produto mudar) ===
            $table->string('product_name');
            $table->string('product_sku')->nullable();
            
            // === PESO/VOLUME ===
            $table->decimal('weight', 10, 2)->nullable()->comment('In kg');
            $table->decimal('volume', 10, 2)->nullable()->comment('In m³');
            
            $table->timestamp('created_at');
            
            // === INDEXES ===
            $table->index('shipment_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_items');
    }
};
