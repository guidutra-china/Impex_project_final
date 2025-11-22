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
            if (!Schema::hasColumn('orders', 'processing_started_at')) {
                $table->timestamp('processing_started_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('orders', 'quoted_at')) {
                $table->timestamp('quoted_at')->nullable()->after('processing_started_at');
            }
            if (!Schema::hasColumn('orders', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('quoted_at');
            }
            if (!Schema::hasColumn('orders', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('completed_at');
            }
            if (!Schema::hasColumn('orders', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable()->after('cancelled_at');
            }
        });

        // Add status timestamps to supplier_quotes
        Schema::table('supplier_quotes', function (Blueprint $table) {
            if (!Schema::hasColumn('supplier_quotes', 'sent_at')) {
                $table->timestamp('sent_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('supplier_quotes', 'accepted_at')) {
                $table->timestamp('accepted_at')->nullable()->after('sent_at');
            }
            if (!Schema::hasColumn('supplier_quotes', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('accepted_at');
            }
            if (!Schema::hasColumn('supplier_quotes', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('rejected_at');
            }
        });

        // Add status timestamps to purchase_orders (sent_at and confirmed_at already exist)
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_orders', 'received_at')) {
                $table->timestamp('received_at')->nullable()->after('confirmed_at');
            }
            if (!Schema::hasColumn('purchase_orders', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('received_at');
            }
            if (!Schema::hasColumn('purchase_orders', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('paid_at');
            }
            if (!Schema::hasColumn('purchase_orders', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable()->after('cancelled_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $columns = ['processing_started_at', 'quoted_at', 'completed_at', 'cancelled_at', 'cancellation_reason'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('supplier_quotes', function (Blueprint $table) {
            $columns = ['sent_at', 'accepted_at', 'rejected_at', 'rejection_reason'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('supplier_quotes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $columns = ['received_at', 'paid_at', 'cancelled_at', 'cancellation_reason'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('purchase_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
