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
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('proforma_invoice_id')
                ->nullable()
                ->after('order_id')
                ->constrained('proforma_invoices')
                ->nullOnDelete();
            
            $table->index('proforma_invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['proforma_invoice_id']);
            $table->dropIndex(['proforma_invoice_id']);
            $table->dropColumn('proforma_invoice_id');
        });
    }
};
