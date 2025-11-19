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
        Schema::create('supplier_payment_allocations', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('supplier_payment_id')->constrained('supplier_payments')->onDelete('cascade');
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->onDelete('restrict');
            
            // === VALOR ALOCADO ===
            $table->bigInteger('allocated_amount')->comment('In cents');
            
            // === TIPO ===
            $table->enum('allocation_type', ['automatic', 'manual'])->default('manual');
            
            // === NOTAS ===
            $table->text('notes')->nullable();
            
            $table->timestamp('created_at');
            
            // === INDEXES ===
            $table->index('supplier_payment_id');
            $table->index('purchase_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_payment_allocations');
    }
};
