<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Remove fields that are now sourced from Shipment to eliminate duplication
     */
    public function up(): void
    {
        Schema::table('commercial_invoices', function (Blueprint $table) {
            // Remove shipping details (now from Shipment)
            if (Schema::hasColumn('commercial_invoices', 'port_of_loading')) {
                $table->dropColumn('port_of_loading');
            }
            if (Schema::hasColumn('commercial_invoices', 'port_of_discharge')) {
                $table->dropColumn('port_of_discharge');
            }
            if (Schema::hasColumn('commercial_invoices', 'final_destination')) {
                $table->dropColumn('final_destination');
            }
            if (Schema::hasColumn('commercial_invoices', 'bl_number')) {
                $table->dropColumn('bl_number');
            }
            if (Schema::hasColumn('commercial_invoices', 'container_numbers')) {
                $table->dropColumn('container_numbers');
            }
            
            // Remove dates (now from Shipment)
            if (Schema::hasColumn('commercial_invoices', 'shipment_date')) {
                $table->dropColumn('shipment_date');
            }
            if (Schema::hasColumn('commercial_invoices', 'invoice_date')) {
                $table->dropColumn('invoice_date');
            }
            
            // Remove incoterm (now from Shipment)
            if (Schema::hasColumn('commercial_invoices', 'incoterm')) {
                $table->dropColumn('incoterm');
            }
            if (Schema::hasColumn('commercial_invoices', 'incoterm_location')) {
                $table->dropColumn('incoterm_location');
            }
            
            // Remove customer/client (now from Shipment)
            if (Schema::hasColumn('commercial_invoices', 'client_id')) {
                $table->dropForeign(['client_id']);
                $table->dropColumn('client_id');
            }
            
            // Remove currency and payment term (now from Proforma via Shipment)
            if (Schema::hasColumn('commercial_invoices', 'currency_id')) {
                $table->dropForeign(['currency_id']);
                $table->dropColumn('currency_id');
            }
            if (Schema::hasColumn('commercial_invoices', 'payment_term_id')) {
                $table->dropForeign(['payment_term_id']);
                $table->dropColumn('payment_term_id');
            }
            
            // Remove proforma_invoice_id (accessed via Shipment)
            if (Schema::hasColumn('commercial_invoices', 'proforma_invoice_id')) {
                $table->dropForeign(['proforma_invoice_id']);
                $table->dropColumn('proforma_invoice_id');
            }
            
            // Remove status and payment fields (not needed for CI)
            if (Schema::hasColumn('commercial_invoices', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('commercial_invoices', 'due_date')) {
                $table->dropColumn('due_date');
            }
            if (Schema::hasColumn('commercial_invoices', 'payment_date')) {
                $table->dropColumn('payment_date');
            }
            if (Schema::hasColumn('commercial_invoices', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
            if (Schema::hasColumn('commercial_invoices', 'payment_reference')) {
                $table->dropColumn('payment_reference');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commercial_invoices', function (Blueprint $table) {
            // Restore shipping details
            $table->string('port_of_loading', 255)->nullable();
            $table->string('port_of_discharge', 255)->nullable();
            $table->string('final_destination', 255)->nullable();
            $table->string('bl_number', 100)->nullable();
            $table->text('container_numbers')->nullable();
            
            // Restore dates
            $table->date('shipment_date')->nullable();
            $table->date('invoice_date')->nullable();
            
            // Restore incoterm
            $table->string('incoterm', 10)->nullable();
            $table->string('incoterm_location', 255)->nullable();
            
            // Restore customer/client
            $table->foreignId('client_id')->nullable()->constrained('clients')->cascadeOnDelete();
            
            // Restore currency and payment term
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->cascadeOnDelete();
            $table->foreignId('payment_term_id')->nullable()->constrained('payment_terms')->cascadeOnDelete();
            
            // Restore proforma_invoice_id
            $table->foreignId('proforma_invoice_id')->nullable()->constrained('proforma_invoices')->cascadeOnDelete();
            
            // Restore status and payment fields
            $table->string('status', 50)->default('draft');
            $table->date('due_date')->nullable();
            $table->date('payment_date')->nullable();
            $table->string('payment_method', 100)->nullable();
            $table->string('payment_reference', 255)->nullable();
        });
    }
};
