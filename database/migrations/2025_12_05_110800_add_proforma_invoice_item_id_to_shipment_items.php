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
        Schema::table('shipment_items', function (Blueprint $table) {
            $table->foreignId('proforma_invoice_item_id')
                ->nullable()
                ->after('sales_invoice_item_id')
                ->constrained('proforma_invoice_items')
                ->nullOnDelete();
            
            $table->index('proforma_invoice_item_id', 'idx_proforma_invoice_item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipment_items', function (Blueprint $table) {
            $table->dropForeign(['proforma_invoice_item_id']);
            $table->dropIndex('idx_proforma_invoice_item');
            $table->dropColumn('proforma_invoice_item_id');
        });
    }
};
