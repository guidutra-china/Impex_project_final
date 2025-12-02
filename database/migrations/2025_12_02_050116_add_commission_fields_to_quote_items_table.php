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
        Schema::table('quote_items', function (Blueprint $table) {
            // Store commission info from order_item for reference and calculation
            $table->decimal('commission_percent', 5, 2)->default(0)->after('supplier_notes');
            $table->enum('commission_type', ['embedded', 'separate'])->default('embedded')->after('commission_percent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quote_items', function (Blueprint $table) {
            $table->dropColumn(['commission_percent', 'commission_type']);
        });
    }
};
