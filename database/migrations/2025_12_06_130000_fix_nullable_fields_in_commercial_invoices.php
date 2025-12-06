<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Fix NOT NULL constraints on commercial_invoices fields
     * These fields should be nullable as they are optional in the form
     */
    public function up(): void
    {
        Schema::table('commercial_invoices', function (Blueprint $table) {
            // Make exporter fields nullable
            $table->text('exporter_name')->nullable()->change();
            $table->text('exporter_address')->nullable()->change();
            $table->string('exporter_tax_id')->nullable()->change();
            $table->string('exporter_country', 100)->nullable()->change();
            
            // Make importer fields nullable
            $table->text('importer_name')->nullable()->change();
            $table->text('importer_address')->nullable()->change();
            $table->string('importer_tax_id')->nullable()->change();
            $table->string('importer_country', 100)->nullable()->change();
            
            // Make bank fields nullable
            $table->string('bank_name')->nullable()->change();
            $table->string('bank_account')->nullable()->change();
            $table->string('bank_swift')->nullable()->change();
            $table->text('bank_address')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse - these fields should always be nullable
        // If you really need to make them NOT NULL again, you would need to:
        // 1. Fill all NULL values with default data
        // 2. Then change columns to NOT NULL
    }
};
