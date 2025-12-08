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
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 50);
            $table->text('description');
            // TODO: `type` enum('payable','receivable') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'payable = Conta a Pagar, receivable = Conta a Receber'
            // TODO: `status` enum('pending','partially_paid','paid','overdue','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending'
            $table->bigInteger('amount');
            $table->bigInteger('paid_amount');
            $table->bigInteger('currency_id');
            $table->decimal('exchange_rate_to_base', 10, 2);
            $table->bigInteger('amount_base_currency');
            $table->date('transaction_date');
            $table->date('due_date');
            $table->date('paid_date')->nullable();
            $table->bigInteger('financial_category_id');
            $table->string('transactable_type', 255)->nullable();
            $table->bigInteger('project_id')->nullable();
            $table->bigInteger('transactable_id')->nullable();
            $table->bigInteger('supplier_id')->nullable();
            $table->bigInteger('client_id')->nullable();
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
        Schema::dropIfExists('financial_transactions');
    }
};
