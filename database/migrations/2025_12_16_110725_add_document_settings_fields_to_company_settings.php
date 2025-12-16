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
        Schema::table('company_settings', function (Blueprint $table) {
            $table->text('po_terms')->nullable()->after('po_prefix');
            $table->string('packing_list_prefix', 10)->default('PL')->after('po_terms');
            $table->string('commercial_invoice_prefix', 10)->default('CI')->after('packing_list_prefix');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn(['po_terms', 'packing_list_prefix', 'commercial_invoice_prefix']);
        });
    }
};
