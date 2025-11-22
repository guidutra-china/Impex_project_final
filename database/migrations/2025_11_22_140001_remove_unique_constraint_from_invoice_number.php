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
        // Drop unique constraint from purchase_invoices.invoice_number
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropUnique(['invoice_number']);
        });

        // Drop unique constraint from sales_invoices.invoice_number
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropUnique(['invoice_number']);
        });

        // Add composite unique constraint on invoice_number + revision_number
        // This ensures each revision has a unique combination
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->unique(['invoice_number', 'revision_number'], 'purchase_invoices_number_revision_unique');
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->unique(['invoice_number', 'revision_number'], 'sales_invoices_number_revision_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop composite unique constraint
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropUnique('purchase_invoices_number_revision_unique');
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropUnique('sales_invoices_number_revision_unique');
        });

        // Restore original unique constraint on invoice_number
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->unique('invoice_number');
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->unique('invoice_number');
        });
    }
};
