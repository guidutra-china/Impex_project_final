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
        // Add shipment_date to purchase_invoices
        if (Schema::hasTable('purchase_invoices') && !Schema::hasColumn('purchase_invoices', 'shipment_date')) {
            Schema::table('purchase_invoices', function (Blueprint $table) {
                $table->date('shipment_date')->nullable()->after('invoice_date');
            });
        }

        // Add shipment_date to sales_invoices
        if (Schema::hasTable('sales_invoices') && !Schema::hasColumn('sales_invoices', 'shipment_date')) {
            Schema::table('sales_invoices', function (Blueprint $table) {
                $table->date('shipment_date')->nullable()->after('invoice_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('purchase_invoices') && Schema::hasColumn('purchase_invoices', 'shipment_date')) {
            Schema::table('purchase_invoices', function (Blueprint $table) {
                $table->dropColumn('shipment_date');
            });
        }

        if (Schema::hasTable('sales_invoices') && Schema::hasColumn('sales_invoices', 'shipment_date')) {
            Schema::table('sales_invoices', function (Blueprint $table) {
                $table->dropColumn('shipment_date');
            });
        }
    }
};
