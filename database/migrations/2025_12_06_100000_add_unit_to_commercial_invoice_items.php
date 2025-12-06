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
        Schema::table('commercial_invoice_items', function (Blueprint $table) {
            if (!Schema::hasColumn('commercial_invoice_items', 'unit')) {
                $table->string('unit', 20)->nullable()->after('quantity')->comment('Unit of measurement (pcs, kg, m, etc.)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commercial_invoice_items', function (Blueprint $table) {
            if (Schema::hasColumn('commercial_invoice_items', 'unit')) {
                $table->dropColumn('unit');
            }
        });
    }
};
