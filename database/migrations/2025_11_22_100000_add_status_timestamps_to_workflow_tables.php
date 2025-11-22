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
        // Add status timestamps to orders (RFQs)
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('processing_started_at')->nullable()->after('status');
            $table->timestamp('quoted_at')->nullable()->after('processing_started_at');
            $table->timestamp('completed_at')->nullable()->after('quoted_at');
            $table->timestamp('cancelled_at')->nullable()->after('completed_at');
            $table->text('cancellation_reason')->nullable()->after('cancelled_at');
        });

        // Add status timestamps to supplier_quotes
        Schema::table('supplier_quotes', function (Blueprint $table) {
            $table->timestamp('sent_at')->nullable()->after('status');
            $table->timestamp('accepted_at')->nullable()->after('sent_at');
            $table->timestamp('rejected_at')->nullable()->after('accepted_at');
            $table->text('rejection_reason')->nullable()->after('rejected_at');
        });

        // Add status timestamps to purchase_orders (sent_at and confirmed_at already exist)
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->timestamp('received_at')->nullable()->after('confirmed_at');
            $table->timestamp('paid_at')->nullable()->after('received_at');
            $table->timestamp('cancelled_at')->nullable()->after('paid_at');
            $table->text('cancellation_reason')->nullable()->after('cancelled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'processing_started_at',
                'quoted_at',
                'completed_at',
                'cancelled_at',
                'cancellation_reason',
            ]);
        });

        Schema::table('supplier_quotes', function (Blueprint $table) {
            $table->dropColumn([
                'sent_at',
                'accepted_at',
                'rejected_at',
                'rejection_reason',
            ]);
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn([
                'received_at',
                'paid_at',
                'cancelled_at',
                'cancellation_reason',
            ]);
        });
    }
};
