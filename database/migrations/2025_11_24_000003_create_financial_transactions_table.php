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
            
            // === IDENTIFICAÇÃO ===
            $table->string('transaction_number', 50)->unique()->comment('Ex: FT-PAY-2025-0001');
            $table->text('description');
            
            // === TIPO E STATUS ===
            $table->enum('type', ['payable', 'receivable'])->comment('payable = Conta a Pagar, receivable = Conta a Receber');
            $table->enum('status', ['pending', 'partially_paid', 'paid', 'overdue', 'cancelled'])->default('pending');
            
            // === VALORES (na moeda da transação) ===
            $table->bigInteger('amount')->comment('Valor total em centavos');
            $table->bigInteger('paid_amount')->default(0)->comment('Valor já pago em centavos');
            
            // === MOEDA ===
            $table->foreignId('currency_id')->constrained('currencies')->onDelete('restrict');
            $table->decimal('exchange_rate_to_base', 12, 6)->comment('Taxa de câmbio para moeda base no dia da transação');
            $table->bigInteger('amount_base_currency')->comment('Valor na moeda base (para relatórios)');
            
            // === DATAS ===
            $table->date('transaction_date')->comment('Data de competência');
            $table->date('due_date')->comment('Data de vencimento');
            $table->date('paid_date')->nullable()->comment('Data de quitação total');
            
            // === CATEGORIA ===
            $table->foreignId('financial_category_id')->constrained('financial_categories')->onDelete('restrict');
            
            // === ORIGEM (Polimórfico) ===
            $table->nullableMorphs('transactable');
            
            // === RELACIONAMENTO (para contas a pagar/receber) ===
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('restrict');
            $table->foreignId('client_id')->nullable()->constrained('clients')->onDelete('restrict');
            
            // === NOTAS ===
            $table->text('notes')->nullable();
            
            // === AUDITORIA ===
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            // === INDEXES ===
            $table->index('transaction_number');
            $table->index('type');
            $table->index('status');
            $table->index('due_date');
            $table->index('transaction_date');
            $table->index(['type', 'status']);
            $table->index(['supplier_id', 'status']);
            $table->index(['client_id', 'status']);
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
