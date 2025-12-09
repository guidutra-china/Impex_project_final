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
            // Add status column if it doesn't exist
            if (!Schema::hasColumn('orders', 'status')) {
                $table->enum('status', ['draft', 'pending', 'sent', 'pending_quotes', 'quotes_received', 'under_analysis', 'approved', 'cancelled', 'completed'])
                    ->default('draft')
                    ->after('customer_nr_rfq');
            }
            
            // Add commission_type column if it doesn't exist
            if (!Schema::hasColumn('orders', 'commission_type')) {
                $table->enum('commission_type', ['embedded', 'separate'])
                    ->default('embedded')
                    ->after('commission_percent');
            }
            
            // Modify columns to be nullable or have defaults if they exist
            if (Schema::hasColumn('orders', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable()->change();
            }
            
            if (Schema::hasColumn('orders', 'commission_percent')) {
                $table->decimal('commission_percent', 10, 2)->default(0)->change();
            }
            
            if (Schema::hasColumn('orders', 'customer_notes')) {
                $table->text('customer_notes')->nullable()->change();
            }
            
            if (Schema::hasColumn('orders', 'notes')) {
                $table->text('notes')->nullable()->change();
            }
            
            if (Schema::hasColumn('orders', 'total_amount')) {
                $table->integer('total_amount')->default(0)->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'status')) {
                $table->dropColumn('status');
            }
            
            if (Schema::hasColumn('orders', 'commission_type')) {
                $table->dropColumn('commission_type');
            }
        });
    }
};
