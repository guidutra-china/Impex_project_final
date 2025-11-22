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
        Schema::create('sales_invoice_purchase_orders', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('sales_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            
            $table->timestamps();
            
            // Unique constraint to prevent duplicates
            $table->unique(['sales_invoice_id', 'purchase_order_id'], 'sales_invoice_po_unique');
            
            // Indexes
            $table->index('sales_invoice_id');
            $table->index('purchase_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_purchase_orders');
    }
};
