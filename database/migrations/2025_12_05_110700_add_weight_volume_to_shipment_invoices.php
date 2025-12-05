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
        Schema::table('shipment_invoices', function (Blueprint $table) {
            $table->decimal('total_weight', 10, 2)->default(0)->after('total_value')->comment('Total weight in kg');
            $table->decimal('total_volume', 12, 4)->default(0)->after('total_weight')->comment('Total volume in m³ (CBM)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipment_invoices', function (Blueprint $table) {
            $table->dropColumn(['total_weight', 'total_volume']);
        });
    }
};
