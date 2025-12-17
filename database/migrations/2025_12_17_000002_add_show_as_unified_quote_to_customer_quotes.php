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
        Schema::table('customer_quotes', function (Blueprint $table) {
            $table->boolean('show_as_unified_quote')->default(false)->after('show_supplier_names');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_quotes', function (Blueprint $table) {
            $table->dropColumn('show_as_unified_quote');
        });
    }
};
