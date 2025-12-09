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
        Schema::table('supplier_quotes', function (Blueprint $table) {
            // Add status column if it doesn't exist
            if (!Schema::hasColumn('supplier_quotes', 'status')) {
                $table->enum('status', ['draft', 'sent', 'accepted', 'rejected'])
                    ->default('draft')
                    ->after('is_latest');
            }
            
            // Add commission_type column if it doesn't exist
            if (!Schema::hasColumn('supplier_quotes', 'commission_type')) {
                $table->enum('commission_type', ['embedded', 'separate'])
                    ->nullable()
                    ->after('locked_exchange_rate_date');
            }
            
            // Modify columns to be nullable or have defaults if they exist
            if (Schema::hasColumn('supplier_quotes', 'revision_number')) {
                $table->integer('revision_number')->default(1)->change();
            }
            
            if (Schema::hasColumn('supplier_quotes', 'is_latest')) {
                $table->boolean('is_latest')->default(true)->change();
            }
            
            if (Schema::hasColumn('supplier_quotes', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->change();
            }
            
            if (Schema::hasColumn('supplier_quotes', 'total_price_before_commission')) {
                $table->integer('total_price_before_commission')->default(0)->change();
            }
            
            if (Schema::hasColumn('supplier_quotes', 'total_price_after_commission')) {
                $table->integer('total_price_after_commission')->default(0)->change();
            }
            
            if (Schema::hasColumn('supplier_quotes', 'commission_amount')) {
                $table->integer('commission_amount')->default(0)->change();
            }
            
            if (Schema::hasColumn('supplier_quotes', 'validity_days')) {
                $table->integer('validity_days')->default(30)->change();
            }
            
            if (Schema::hasColumn('supplier_quotes', 'supplier_notes')) {
                $table->text('supplier_notes')->nullable()->change();
            }
            
            if (Schema::hasColumn('supplier_quotes', 'notes')) {
                $table->text('notes')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_quotes', function (Blueprint $table) {
            if (Schema::hasColumn('supplier_quotes', 'status')) {
                $table->dropColumn('status');
            }
            
            if (Schema::hasColumn('supplier_quotes', 'commission_type')) {
                $table->dropColumn('commission_type');
            }
        });
    }
};
