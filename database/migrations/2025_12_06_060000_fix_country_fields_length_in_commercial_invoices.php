<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Change country fields from VARCHAR(2) to VARCHAR(100) to support full country names
        DB::statement('ALTER TABLE `commercial_invoices` MODIFY `exporter_country` VARCHAR(100) NULL');
        DB::statement('ALTER TABLE `commercial_invoices` MODIFY `importer_country` VARCHAR(100) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE `commercial_invoices` MODIFY `exporter_country` VARCHAR(2) NULL');
        DB::statement('ALTER TABLE `commercial_invoices` MODIFY `importer_country` VARCHAR(2) NULL');
    }
};
