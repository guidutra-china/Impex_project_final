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
            $table->bigInteger('shipment_id');
            $table->string('invoice_number', 50);
            $table->text('exporter_name');
            $table->text('exporter_address');
            $table->string('exporter_tax_id', 255)->nullable();
            $table->string('exporter_country', 100)->nullable();
            $table->string('exporter_phone', 255)->nullable();
            $table->string('exporter_email', 255)->nullable();
            $table->text('importer_name');
            $table->text('importer_address');
            $table->string('importer_tax_id', 255)->nullable();
            $table->string('importer_country', 100)->nullable();
            $table->string('importer_phone', 255)->nullable();
            $table->string('importer_email', 255)->nullable();
            $table->string('notify_party_name', 255)->nullable();
            $table->text('notify_party_address');
            $table->string('notify_party_phone', 255)->nullable();
            $table->string('country_of_origin', 2)->nullable();
            $table->string('country_of_destination', 2)->nullable();
            $table->string('vessel_flight_number', 255)->nullable();
            $table->string('payment_terms', 255)->nullable();
            $table->text('terms_of_sale');
            $table->bigInteger('subtotal');
            $table->bigInteger('freight_cost');
            $table->bigInteger('insurance_cost');
            $table->bigInteger('other_costs');
            $table->bigInteger('total_value');
            // TODO: `reason_for_export` enum('sale','sample','return','repair','gift','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sale'
            $table->text('declaration');
            $table->text('additional_notes');
            $table->timestamp('issued_at')->nullable();
            $table->bigInteger('issued_by')->nullable();
            $table->string('bank_name', 255)->nullable();
            $table->string('bank_account', 255)->nullable();
            $table->string('bank_swift', 255)->nullable();
            $table->text('bank_address');
            $table->decimal('customs_discount_percentage', 10, 2);
            // TODO: `display_options` json DEFAULT NULL
            $table->text('notes');
            $table->text('terms_and_conditions');
            $table->timestamps();
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
