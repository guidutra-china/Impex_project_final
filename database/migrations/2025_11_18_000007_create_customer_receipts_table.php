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
        Schema::create('customer_receipts', function (Blueprint $table) {
            $table->id();
            
            // === IDENTIFICAÇÃO ===
            $table->string('receipt_number', 50)->unique()->comment('Ex: REC-CUST-2025-0001');
            
            // === CLIENTE ===
            $table->foreignId('client_id')->constrained('clients')->onDelete('restrict');
            
            // === RECEBIMENTO ===
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
            $table->date('receipt_date');
            
            // === REFERÊNCIA ===
            $table->string('reference_number')->nullable();
            $table->string('transaction_id')->nullable();
            
            // === STATUS ===
            $table->enum('status', ['pending', 'processing', 'received', 'failed', 'cancelled'])->default('pending');
            
            // === NOTAS ===
            $table->text('notes')->nullable();
            
            // === AUDITORIA ===
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // === INDEXES ===
            $table->index('receipt_number');
            $table->index('client_id');
            $table->index('receipt_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_receipts');
    }
};
