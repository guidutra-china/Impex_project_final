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
        Schema::table('orders', function (Blueprint $table) {
            // Keep existing commission_percent and commission_type as defaults for new items
            // These fields already exist, just adding comment for clarity
            
            // Add a helper field to store calculated average (can be updated via observer)
            $table->decimal('commission_percent_average', 5, 2)->nullable()->after('commission_type')
                ->comment('Calculated average commission across all order items');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('commission_percent_average');
        });
    }
};
