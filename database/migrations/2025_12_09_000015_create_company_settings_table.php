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
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name', 255);
            $table->string('logo_path', 255)->nullable();
            $table->text('address');
            $table->string('city', 255)->nullable();
            $table->string('state', 255)->nullable();
            $table->string('zip_code', 255)->nullable();
            $table->string('country', 255)->nullable();
            $table->string('phone', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('website', 255)->nullable();
            $table->string('tax_id', 255)->nullable();
            $table->string('registration_number', 255)->nullable();
            $table->string('bank_name', 255)->nullable();
            $table->string('bank_account_number', 255)->nullable();
            $table->string('bank_routing_number', 255)->nullable();
            $table->string('bank_swift_code', 255)->nullable();
            $table->text('footer_text');
            $table->string('invoice_prefix', 255);
            $table->string('quote_prefix', 255);
            $table->string('po_prefix', 255);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
