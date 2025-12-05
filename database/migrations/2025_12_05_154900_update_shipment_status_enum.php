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
        // MySQL doesn't support direct ALTER COLUMN for ENUM
        // We need to use raw SQL to modify the enum
        DB::statement("ALTER TABLE shipments MODIFY COLUMN status ENUM(
            'pending',
            'preparing',
            'ready_to_ship',
            'picked_up',
            'on_board',
            'customs_clearance',
            'out_for_delivery',
            'delivered',
            'cancelled',
            'returned'
        ) NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to old enum values
        DB::statement("ALTER TABLE shipments MODIFY COLUMN status ENUM(
            'pending',
            'preparing',
            'ready_to_ship',
            'picked_up',
            'in_transit',
            'customs_clearance',
            'out_for_delivery',
            'delivered',
            'cancelled',
            'returned'
        ) NOT NULL DEFAULT 'pending'");
    }
};
