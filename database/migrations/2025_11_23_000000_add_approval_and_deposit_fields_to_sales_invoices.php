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
        Schema::table('sales_invoices', function (Blueprint $table) {
            // Approval fields
            $table->string('approval_status')->default('pending_approval')->after('status');
            $table->timestamp('approval_deadline')->nullable()->after('approval_status');
            $table->timestamp('approved_at')->nullable()->after('approval_deadline');
            $table->string('approved_by')->nullable()->after('approved_at');
            $table->text('rejection_reason')->nullable()->after('approved_by');
            
            // Deposit fields (will use Payment Terms for percentages)
            $table->boolean('deposit_required')->default(false)->after('rejection_reason');
            $table->integer('deposit_amount')->nullable()->after('deposit_required')->comment('Amount in cents');
            $table->boolean('deposit_received')->default(false)->after('deposit_amount');
            $table->timestamp('deposit_received_at')->nullable()->after('deposit_received');
            $table->string('deposit_payment_method')->nullable()->after('deposit_received_at');
            $table->string('deposit_payment_reference')->nullable()->after('deposit_payment_method');
            
            // Add index for approval_status for faster queries
            $table->index('approval_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropIndex(['approval_status']);
            
            $table->dropColumn([
                'approval_status',
                'approval_deadline',
                'approved_at',
                'approved_by',
                'rejection_reason',
                'deposit_required',
                'deposit_amount',
                'deposit_received',
                'deposit_received_at',
                'deposit_payment_method',
                'deposit_payment_reference',
            ]);
        });
    }
};
