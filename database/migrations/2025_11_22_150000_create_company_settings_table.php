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
            
            // Basic Information
            $table->string('company_name');
            $table->string('logo_path')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('country')->nullable();
            
            // Contact Information
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            
            // Legal Information
            $table->string('tax_id')->nullable();
            $table->string('registration_number')->nullable();
            
            // Banking Information
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_routing_number')->nullable();
            $table->string('bank_swift_code')->nullable();
            
            // Additional Settings
            $table->text('footer_text')->nullable();
            $table->string('invoice_prefix')->default('INV');
            $table->string('quote_prefix')->default('QT');
            $table->string('po_prefix')->default('PO');
            
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
