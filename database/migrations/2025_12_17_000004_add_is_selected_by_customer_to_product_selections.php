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
        Schema::table('customer_quote_product_selections', function (Blueprint $table) {
            $table->boolean('is_selected_by_customer')->default(false)->after('is_visible_to_customer');
            $table->timestamp('selected_at')->nullable()->after('is_selected_by_customer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_quote_product_selections', function (Blueprint $table) {
            $table->dropColumn(['is_selected_by_customer', 'selected_at']);
        });
    }
};
