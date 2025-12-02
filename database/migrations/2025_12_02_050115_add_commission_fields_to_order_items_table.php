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
        Schema::table('order_items', function (Blueprint $table) {
            // Add commission percentage for this specific product (0-100)
            $table->decimal('commission_percent', 5, 2)->default(0)->after('requested_unit_price');
            
            // Add commission type for this specific product
            $table->enum('commission_type', ['embedded', 'separate'])->default('embedded')->after('commission_percent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['commission_percent', 'commission_type']);
        });
    }
};
