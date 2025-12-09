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
            $table->integer('revision_number')->default(1);
            $table->boolean('is_latest')->default(true);
            $table->enum('status', ['draft', 'sent', 'accepted', 'rejected'])->default('draft');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->integer('total_price_before_commission')->default(0);
            $table->integer('total_price_after_commission')->default(0);
            $table->integer('moq')->nullable();
            $table->integer('lead_time_days')->nullable();
            $table->string('incoterm', 50)->nullable();
            $table->string('payment_terms', 100)->nullable();
            $table->integer('commission_amount')->default(0);
            $table->decimal('locked_exchange_rate', 10, 2)->nullable();
            $table->date('locked_exchange_rate_date')->nullable();
            $table->enum('commission_type', ['embedded', 'separate'])->nullable();
            $table->date('valid_until')->nullable();
            $table->integer('validity_days')->default(30);
            $table->text('supplier_notes')->nullable();
            $table->text('notes')->nullable();
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
