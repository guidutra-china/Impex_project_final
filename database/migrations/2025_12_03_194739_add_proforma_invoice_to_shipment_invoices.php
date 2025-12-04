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
        Schema::table('shipment_invoices', function (Blueprint $table) {
            $table->foreignId('proforma_invoice_id')->nullable()->after('sales_invoice_id')->constrained();
            $table->enum('status', ['pending', 'partial_shipped', 'fully_shipped'])->default('pending')->after('total_value');
            $table->timestamp('shipped_at')->nullable()->after('status');

            $table->index(['proforma_invoice_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipment_invoices', function (Blueprint $table) {
            $table->dropIndex(['proforma_invoice_id', 'status']);
            $table->dropColumn(['proforma_invoice_id', 'status', 'shipped_at']);
        });
    }
};
