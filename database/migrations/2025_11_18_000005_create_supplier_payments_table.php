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
        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            
            // === IDENTIFICAÇÃO ===
            $table->string('payment_number', 50)->unique()->comment('Ex: PAY-SUP-2025-0001');
            
            // === FORNECEDOR ===
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('restrict');
            
            // === PAGAMENTO ===
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->onDelete('restrict');
            $table->foreignId('payment_method_id')->constrained('payment_methods')->onDelete('restrict');
            
            // === VALORES ===
            $table->foreignId('currency_id')->constrained('currencies')->onDelete('restrict');
            $table->bigInteger('amount')->comment('In cents');
            $table->bigInteger('fee')->default(0)->comment('In cents');
            $table->bigInteger('net_amount')->comment('In cents (amount - fee)');
            
            // === CONVERSÃO ===
            $table->decimal('exchange_rate', 12, 6)->nullable();
            $table->bigInteger('amount_base_currency')->nullable()->comment('In cents');
            
            // === DATA ===
            $table->date('payment_date');
            
            // === REFERÊNCIA ===
            $table->string('reference_number')->nullable();
            $table->string('transaction_id')->nullable();
            
            // === STATUS ===
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            
            // === NOTAS ===
            $table->text('notes')->nullable();
            
            // === AUDITORIA ===
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // === INDEXES ===
            $table->index('payment_number');
            $table->index('supplier_id');
            $table->index('payment_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
    }
};
