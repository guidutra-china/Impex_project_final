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
        Schema::table('financial_transactions', function (Blueprint $table) {
            // Add project_id to link transactions to RFQs/Projects
            $table->foreignId('project_id')
                ->nullable()
                ->after('transactable_type')
                ->constrained('orders')
                ->nullOnDelete()
                ->comment('RFQ/Project this transaction belongs to for expense tracking');
            
            // Add index for better query performance
            $table->index('project_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropIndex(['project_id']);
            $table->dropColumn('project_id');
        });
    }
};
