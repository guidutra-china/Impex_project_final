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
        Schema::create('shipment_proforma_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->foreignId('proforma_invoice_id')->constrained('proforma_invoices')->cascadeOnDelete();
            
            // Quantity tracking
            $table->integer('quantity_shipped')->default(0)->comment('Total units shipped from this proforma');
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Unique constraint with shorter name to avoid MySQL identifier length limit
            $table->unique(['shipment_id', 'proforma_invoice_id'], 'uq_shipment_proforma');
            
            // Indexes
            $table->index('shipment_id');
            $table->index('proforma_invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_proforma_invoices');
    }
};
