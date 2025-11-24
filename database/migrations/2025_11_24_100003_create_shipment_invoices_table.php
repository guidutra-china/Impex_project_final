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
        Schema::create('shipment_invoices', function (Blueprint $table) {
            $table->id();
            
            // === RELATIONSHIPS ===
            $table->foreignId('shipment_id')->constrained('shipments')->onDelete('cascade');
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices')->onDelete('cascade');
            
            // === SUMMARY DATA ===
            $table->integer('total_items')->default(0)->comment('Count of items from this invoice');
            $table->integer('total_quantity')->default(0)->comment('Total quantity from this invoice');
            $table->bigInteger('total_value')->default(0)->comment('Total value in cents');
            
            // === NOTES ===
            $table->text('notes')->nullable()->comment('Notes specific to this invoice in this shipment');
            
            // === TIMESTAMPS ===
            $table->timestamps();
            
            // === INDEXES ===
            $table->unique(['shipment_id', 'sales_invoice_id'], 'idx_shipment_invoice_unique');
            $table->index('sales_invoice_id', 'idx_sales_invoice');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_invoices');
    }
};
