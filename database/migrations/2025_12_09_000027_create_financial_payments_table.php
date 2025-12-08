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
        Schema::create('financial_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 50);
            $table->text('description');
            // TODO: `type` enum('debit','credit') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'debit = Saída (Pagamento), credit = Entrada (Recebimento)'
            $table->bigInteger('bank_account_id');
            $table->bigInteger('payment_method_id');
            $table->date('payment_date');
            $table->bigInteger('amount');
            $table->bigInteger('fee');
            $table->bigInteger('net_amount');
            $table->bigInteger('currency_id');
            $table->decimal('exchange_rate_to_base', 10, 2);
            $table->bigInteger('amount_base_currency');
            $table->string('reference_number', 255)->nullable();
            $table->string('transaction_id', 255)->nullable();
            // TODO: `status` enum('pending','processing','completed','failed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'completed'
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
        Schema::dropIfExists('financial_payments');
    }
};
