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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            // TODO: `type` enum('bank_transfer','wire_transfer','paypal','credit_card','debit_card','check','cash','wise','cryptocurrency','other') COLLATE utf8mb4_unicode_ci NOT NULL
            $table->bigInteger('bank_account_id')->nullable();
            // TODO: `fee_type` enum('none','fixed','percentage','fixed_plus_percentage') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none'
            $table->bigInteger('fixed_fee');
            $table->decimal('percentage_fee', 10, 2);
            // TODO: `processing_time` enum('immediate','same_day','1_3_days','3_5_days','5_7_days') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'immediate'
            $table->integer('is_active');
            $table->text('notes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
