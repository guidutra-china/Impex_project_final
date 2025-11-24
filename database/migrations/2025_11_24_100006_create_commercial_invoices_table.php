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
        Schema::create('commercial_invoices', function (Blueprint $table) {
            $table->id();
            
            // === RELATIONSHIP ===
            $table->foreignId('shipment_id')->unique()->constrained('shipments')->onDelete('cascade');
            
            // === INVOICE INFORMATION ===
            $table->string('invoice_number', 50)->unique()->comment('Ex: CI-2025-0001');
            $table->date('invoice_date');
            
            // === EXPORTER (Seller) ===
            $table->string('exporter_name');
            $table->text('exporter_address');
            $table->string('exporter_tax_id')->nullable()->comment('Tax ID / EIN / VAT');
            $table->string('exporter_country', 2)->comment('ISO 2-letter country code');
            $table->string('exporter_phone')->nullable();
            $table->string('exporter_email')->nullable();
            
            // === IMPORTER (Buyer) ===
            $table->string('importer_name');
            $table->text('importer_address');
            $table->string('importer_tax_id')->nullable();
            $table->string('importer_country', 2)->comment('ISO 2-letter country code');
            $table->string('importer_phone')->nullable();
            $table->string('importer_email')->nullable();
            
            // === NOTIFY PARTY (if different from importer) ===
            $table->string('notify_party_name')->nullable();
            $table->text('notify_party_address')->nullable();
            $table->string('notify_party_phone')->nullable();
            
            // === SHIPPING DETAILS ===
            $table->string('port_of_loading')->nullable()->comment('Ex: Shanghai, China');
            $table->string('port_of_discharge')->nullable()->comment('Ex: Los Angeles, USA');
            $table->string('country_of_origin', 2)->nullable()->comment('ISO 2-letter code');
            $table->string('country_of_destination', 2)->nullable()->comment('ISO 2-letter code');
            $table->string('vessel_flight_number')->nullable()->comment('Vessel name or flight number');
            
            // === TERMS ===
            $table->string('incoterm', 10)->nullable()->comment('FOB, CIF, EXW, DDP, etc.');
            $table->string('payment_terms')->nullable()->comment('Ex: 30 days net');
            $table->text('terms_of_sale')->nullable();
            
            // === TOTALS ===
            $table->foreignId('currency_id')->constrained('currencies')->onDelete('restrict');
            $table->bigInteger('subtotal')->default(0)->comment('In cents');
            $table->bigInteger('freight_cost')->default(0)->comment('In cents');
            $table->bigInteger('insurance_cost')->default(0)->comment('In cents');
            $table->bigInteger('other_costs')->default(0)->comment('In cents');
            $table->bigInteger('total_value')->default(0)->comment('In cents');
            
            // === ADDITIONAL INFO ===
            $table->enum('reason_for_export', ['sale', 'sample', 'return', 'repair', 'gift', 'other'])->default('sale');
            $table->text('declaration')->nullable()->comment('Customs declaration text');
            $table->text('additional_notes')->nullable();
            
            // === STATUS ===
            $table->enum('status', ['draft', 'issued', 'submitted', 'cleared', 'cancelled'])->default('draft');
            $table->timestamp('issued_at')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->onDelete('set null');
            
            // === TIMESTAMPS ===
            $table->timestamps();
            
            // === INDEXES ===
            $table->index('status', 'idx_status');
            $table->index('invoice_date', 'idx_invoice_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commercial_invoices');
    }
};
