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
        // Products table already has comprehensive packing information:
        // - pcs_per_inner_box: Units per inner box (standard packaging)
        // - inner_box_weight: Weight of inner box
        // - inner_box_length, width, height: Inner box dimensions
        // - pcs_per_carton: Units per master carton
        // - carton_weight: Weight of master carton
        // - carton_length, width, height: Master carton dimensions
        // - carton_cbm: Volume of master carton
        //
        // No additional columns needed - use existing fields for packaging logic
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No changes to reverse
    }
};
