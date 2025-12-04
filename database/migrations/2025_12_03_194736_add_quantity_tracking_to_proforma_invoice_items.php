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
        Schema::table('proforma_invoice_items', function (Blueprint $table) {
            $table->integer('quantity_shipped')->default(0)->after('quantity');
            $table->integer('quantity_remaining')->default(0)->after('quantity_shipped');
            $table->integer('shipment_count')->default(0)->after('quantity_remaining');

            // Índices para performance
            $table->index(['proforma_invoice_id', 'quantity_shipped'], 'pii_shipped_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proforma_invoice_items', function (Blueprint $table) {
            $table->dropIndex('pii_shipped_idx');
            $table->dropColumn(['quantity_shipped', 'quantity_remaining', 'shipment_count']);
        });
    }
};
