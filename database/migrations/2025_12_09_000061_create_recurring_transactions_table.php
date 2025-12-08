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
        Schema::create('recurring_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->text('description');
            // TODO: `type` enum('payable','receivable') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'payable = Despesa Recorrente, receivable = Receita Recorrente'
            $table->bigInteger('financial_category_id');
            $table->bigInteger('amount');
            $table->bigInteger('currency_id');
            // TODO: `frequency` enum('daily','weekly','monthly','quarterly','yearly') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'monthly'
            $table->integer('interval');
            $table->integer('day_of_month')->nullable();
            $table->integer('day_of_week')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_due_date');
            $table->bigInteger('supplier_id')->nullable();
            $table->bigInteger('client_id')->nullable();
            $table->integer('is_active');
            $table->integer('auto_generate');
            $table->integer('days_before_due');
            $table->date('last_generated_date')->nullable();
            $table->bigInteger('last_generated_transaction_id')->nullable();
            $table->text('notes');
            $table->bigInteger('created_by')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_transactions');
    }
};
