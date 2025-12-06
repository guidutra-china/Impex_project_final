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
        Schema::table('supplier_quotes', function (Blueprint $table) {
            // MOQ - Minimum Order Quantity
            $table->integer('moq')->nullable()->after('total_price_after_commission')
                ->comment('Minimum Order Quantity required by supplier');
            
            // Lead Time in days
            $table->integer('lead_time_days')->nullable()->after('moq')
                ->comment('Production + shipping time in days');
            
            // Incoterm (FOB, CIF, EXW, DDP, etc.)
            $table->string('incoterm', 50)->nullable()->after('lead_time_days')
                ->comment('International Commercial Terms (FOB, CIF, DDP, etc.)');
            
            // Payment Terms
            $table->string('payment_terms', 100)->nullable()->after('incoterm')
                ->comment('Payment conditions (100% Advance, 30/70, Net 30, L/C, etc.)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_quotes', function (Blueprint $table) {
            $table->dropColumn(['moq', 'lead_time_days', 'incoterm', 'payment_terms']);
        });
    }
};
