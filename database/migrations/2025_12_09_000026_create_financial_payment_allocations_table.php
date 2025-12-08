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
        Schema::create('financial_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('financial_payment_id');
            $table->bigInteger('financial_transaction_id');
            $table->bigInteger('allocated_amount');
            $table->bigInteger('gain_loss_on_exchange');
            // TODO: `allocation_type` enum('automatic','manual') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual'
            $table->text('notes');
            $table->bigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_payment_allocations');
    }
};
