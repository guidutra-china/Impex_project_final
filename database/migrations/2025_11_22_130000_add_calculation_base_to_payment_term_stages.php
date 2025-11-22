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
        Schema::table('payment_term_stages', function (Blueprint $table) {
            // Rename days_from_invoice to days for flexibility
            $table->renameColumn('days_from_invoice', 'days');
        });

        Schema::table('payment_term_stages', function (Blueprint $table) {
            // Add calculation_base field: 'invoice_date' or 'shipment_date'
            $table->enum('calculation_base', ['invoice_date', 'shipment_date'])
                ->default('invoice_date')
                ->after('days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_term_stages', function (Blueprint $table) {
            $table->dropColumn('calculation_base');
        });

        Schema::table('payment_term_stages', function (Blueprint $table) {
            $table->renameColumn('days', 'days_from_invoice');
        });
    }
};
