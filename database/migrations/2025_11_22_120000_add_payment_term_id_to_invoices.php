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
        // Add payment_term_id to purchase_invoices
        if (Schema::hasTable('purchase_invoices') && !Schema::hasColumn('purchase_invoices', 'payment_term_id')) {
            Schema::table('purchase_invoices', function (Blueprint $table) {
                $table->foreignId('payment_term_id')->nullable()->after('supplier_id')->constrained()->nullOnDelete();
            });
        }

        // Add payment_term_id to sales_invoices
        if (Schema::hasTable('sales_invoices') && !Schema::hasColumn('sales_invoices', 'payment_term_id')) {
            Schema::table('sales_invoices', function (Blueprint $table) {
                $table->foreignId('payment_term_id')->nullable()->after('client_id')->constrained()->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('purchase_invoices') && Schema::hasColumn('purchase_invoices', 'payment_term_id')) {
            Schema::table('purchase_invoices', function (Blueprint $table) {
                $table->dropForeign(['payment_term_id']);
                $table->dropColumn('payment_term_id');
            });
        }

        if (Schema::hasTable('sales_invoices') && Schema::hasColumn('sales_invoices', 'payment_term_id')) {
            Schema::table('sales_invoices', function (Blueprint $table) {
                $table->dropForeign(['payment_term_id']);
                $table->dropColumn('payment_term_id');
            });
        }
    }
};
