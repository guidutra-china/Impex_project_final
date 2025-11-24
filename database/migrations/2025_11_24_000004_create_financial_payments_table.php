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
            
            // === IDENTIFICAÇÃO ===
            $table->string('payment_number', 50)->unique()->comment('Ex: FP-OUT-2025-0001 ou FP-IN-2025-0001');
            $table->text('description');
            
            // === TIPO ===
            $table->enum('type', ['debit', 'credit'])->comment('debit = Saída (Pagamento), credit = Entrada (Recebimento)');
            
            // === CONTA BANCÁRIA E MÉTODO ===
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->onDelete('restrict');
            $table->foreignId('payment_method_id')->constrained('payment_methods')->onDelete('restrict');
            
            // === DATA ===
            $table->date('payment_date');
            
            // === VALORES (na moeda do pagamento) ===
            $table->bigInteger('amount')->comment('Valor total em centavos');
            $table->bigInteger('fee')->default(0)->comment('Taxas bancárias em centavos');
            $table->bigInteger('net_amount')->comment('Valor líquido (amount - fee)');
            
            // === MOEDA ===
            $table->foreignId('currency_id')->constrained('currencies')->onDelete('restrict');
            $table->decimal('exchange_rate_to_base', 12, 6)->comment('Taxa de câmbio para moeda base no dia do pagamento');
            $table->bigInteger('amount_base_currency')->comment('Valor na moeda base');
            
            // === REFERÊNCIA ===
            $table->string('reference_number')->nullable()->comment('Número do documento, cheque, transferência');
            $table->string('transaction_id')->nullable()->comment('ID da transação bancária');
            
            // === STATUS ===
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('completed');
            
            // === RELACIONAMENTO (opcional) ===
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('restrict');
            $table->foreignId('client_id')->nullable()->constrained('clients')->onDelete('restrict');
            
            // === NOTAS ===
            $table->text('notes')->nullable();
            
            // === AUDITORIA ===
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            // === INDEXES ===
            $table->index('payment_number');
            $table->index('type');
            $table->index('payment_date');
            $table->index('status');
            $table->index('bank_account_id');
            $table->index(['type', 'status']);
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
