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
        Schema::create('supplier_quotes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('order_id');
            $table->bigInteger('supplier_id');
            $table->bigInteger('currency_id')->nullable();
            $table->string('quote_number', 255)->nullable();
            $table->integer('revision_number');
            $table->integer('is_latest');
            // TODO: `status` enum('draft','sent','accepted','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft'
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason');
            $table->integer('total_price_before_commission');
            $table->integer('total_price_after_commission');
            $table->integer('moq')->nullable();
            $table->integer('lead_time_days')->nullable();
            $table->string('incoterm', 50)->nullable();
            $table->string('payment_terms', 100)->nullable();
            $table->integer('commission_amount');
            $table->decimal('locked_exchange_rate', 10, 2)->nullable();
            $table->date('locked_exchange_rate_date')->nullable();
            // TODO: `commission_type` enum('embedded','separate') COLLATE utf8mb4_unicode_ci DEFAULT NULL
            $table->date('valid_until')->nullable();
            $table->integer('validity_days');
            $table->text('supplier_notes');
            $table->text('notes');
            $table->bigInteger('created_by')->nullable();
            $table->bigInteger('updated_by')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_quotes');
    }
};
