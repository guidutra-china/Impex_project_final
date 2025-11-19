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
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            
            // === QUANTIDADE ===
            $table->integer('quantity');
            $table->integer('received_quantity')->default(0);
            $table->integer('allocated_quantity')->default(0)->comment('Quantidade já alocada para vendas');
            
            // === CUSTO (em centavos) ===
            $table->bigInteger('unit_cost');
            $table->bigInteger('total_cost');
            
            // === PREÇO DE VENDA (opcional, para cálculo de margem) ===
            $table->bigInteger('selling_price')->nullable();
            $table->bigInteger('selling_total')->nullable();
            
            // === SNAPSHOT (não muda se produto mudar) ===
            $table->string('product_name');
            $table->string('product_sku')->nullable();
            
            // === DELIVERY ===
            $table->date('expected_delivery_date')->nullable();
            $table->date('actual_delivery_date')->nullable();
            
            // === NOTAS ===
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // === INDEXES ===
            $table->index('purchase_order_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
