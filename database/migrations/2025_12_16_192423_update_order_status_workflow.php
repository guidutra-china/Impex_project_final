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
        // First, migrate existing statuses to new values
        DB::table('orders')->where('status', 'pending')->update(['status' => 'draft']);
        DB::table('orders')->where('status', 'sent')->update(['status' => 'sent']);
        DB::table('orders')->where('status', 'pending_quotes')->update(['status' => 'sent']);
        DB::table('orders')->where('status', 'quotes_received')->update(['status' => 'under_analysis']);
        DB::table('orders')->where('status', 'under_analysis')->update(['status' => 'under_analysis']);
        DB::table('orders')->where('status', 'approved')->update(['status' => 'approved']);
        DB::table('orders')->where('status', 'cancelled')->update(['status' => 'cancelled']);
        DB::table('orders')->where('status', 'completed')->update(['status' => 'completed']);
        
        // Update the enum to only allow new values
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('draft', 'sent', 'under_analysis', 'approved', 'cancelled', 'completed') NOT NULL DEFAULT 'draft'");
        
        // Add new timestamp fields
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('sent_at')->nullable()->after('status');
            $table->timestamp('under_analysis_at')->nullable()->after('sent_at');
            $table->timestamp('approved_at')->nullable()->after('under_analysis_at');
            // completed_at and cancelled_at already exist
        });
        
        // Migrate existing timestamps
        DB::statement("UPDATE orders SET sent_at = rfq_generated_at WHERE rfq_generated_at IS NOT NULL AND sent_at IS NULL");
        DB::statement("UPDATE orders SET under_analysis_at = processing_started_at WHERE processing_started_at IS NOT NULL AND under_analysis_at IS NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore old enum values
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('draft', 'pending', 'sent', 'pending_quotes', 'quotes_received', 'under_analysis', 'approved', 'cancelled', 'completed') NOT NULL DEFAULT 'draft'");
        
        // Remove new timestamp fields
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['sent_at', 'under_analysis_at', 'approved_at']);
        });
    }
};
