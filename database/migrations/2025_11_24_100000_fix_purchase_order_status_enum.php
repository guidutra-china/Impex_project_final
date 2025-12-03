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

        // Update existing values to match new enum
        DB::table('purchase_orders')
            ->where('status', 'approved')
            ->update(['status' => 'confirmed']);
        
        DB::table('purchase_orders')
            ->where('status', 'pending_approval')
            ->update(['status' => 'draft']);
        
        DB::table('purchase_orders')
            ->where('status', 'partially_received')
            ->update(['status' => 'received']);
        
        DB::table('purchase_orders')
            ->where('status', 'closed')
            ->update(['status' => 'paid']);

        // Alter the enum column (MySQL only)
        DB::statement("ALTER TABLE purchase_orders MODIFY COLUMN status ENUM('draft', 'sent', 'confirmed', 'received', 'paid', 'cancelled') NOT NULL DEFAULT 'draft'");
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

        // Reverse the mapping
        DB::table('purchase_orders')
            ->where('status', 'confirmed')
            ->update(['status' => 'approved']);
        
        DB::table('purchase_orders')
            ->where('status', 'paid')
            ->update(['status' => 'closed']);

        // Restore original enum (MySQL only)
        DB::statement("ALTER TABLE purchase_orders MODIFY COLUMN status ENUM('draft', 'pending_approval', 'approved', 'sent', 'confirmed', 'partially_received', 'received', 'cancelled', 'closed') NOT NULL DEFAULT 'draft'");
    }
};
