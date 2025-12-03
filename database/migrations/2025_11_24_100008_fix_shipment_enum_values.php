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
        // Only run this migration on MySQL databases
        // SQLite doesn't support MODIFY COLUMN for enums
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Fix shipment_type enum
        DB::statement("ALTER TABLE `shipments` MODIFY COLUMN `shipment_type` ENUM('outbound', 'inbound') DEFAULT 'outbound'");
        
        // Fix status enum - add new statuses
        DB::statement("ALTER TABLE `shipments` MODIFY COLUMN `status` ENUM(
            'draft',
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
        ) DEFAULT 'draft'");
        
        // Fix packing_status if exists
        if (Schema::hasColumn('shipment_items', 'packing_status')) {
            DB::statement("ALTER TABLE `shipment_items` MODIFY COLUMN `packing_status` ENUM('unpacked', 'partially_packed', 'fully_packed') DEFAULT 'unpacked'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only run this migration on MySQL databases
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Revert to original values
        DB::statement("ALTER TABLE `shipments` MODIFY COLUMN `shipment_type` ENUM('outgoing', 'incoming') DEFAULT 'outgoing'");
        
        DB::statement("ALTER TABLE `shipments` MODIFY COLUMN `status` ENUM(
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
        ) DEFAULT 'pending'");
    }
};
