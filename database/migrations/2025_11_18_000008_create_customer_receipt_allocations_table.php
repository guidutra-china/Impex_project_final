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
        Schema::create('customer_receipt_allocations', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('customer_receipt_id')->constrained('customer_receipts')->onDelete('cascade');
            $table->foreignId('sales_order_id')->constrained('orders')->onDelete('restrict')->comment('Sales Order');
            
            // === VALOR ALOCADO ===
            $table->bigInteger('allocated_amount')->comment('In cents');
            
            // === TIPO ===
            $table->enum('allocation_type', ['automatic', 'manual'])->default('manual');
            
            // === NOTAS ===
            $table->text('notes')->nullable();
            
            $table->timestamp('created_at');
            
            // === INDEXES ===
            $table->index('customer_receipt_id');
            $table->index('sales_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_receipt_allocations');
    }
};
