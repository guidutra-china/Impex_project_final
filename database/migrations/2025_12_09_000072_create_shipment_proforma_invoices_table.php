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
            $table->bigInteger('shipment_id');
            $table->bigInteger('proforma_invoice_id');
            $table->integer('quantity_shipped');
            $table->bigInteger('created_by')->nullable();
            $table->timestamps();
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
