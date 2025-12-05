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
        // First, update existing values to new equivalents
        DB::table('shipments')
            ->where('status', 'draft')
            ->update(['status' => 'pending']);
        
        DB::table('shipments')
            ->where('status', 'confirmed')
            ->update(['status' => 'ready_to_ship']);
        
        DB::table('shipments')
            ->where('status', 'in_transit')
            ->update(['status' => 'on_board']);
        
        // Now modify the enum to only include new values
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
