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
        Schema::create('financial_payment_allocations', function (Blueprint $table) {
            $table->id();
            
            // === RELACIONAMENTOS ===
            $table->foreignId('financial_payment_id')->constrained('financial_payments')->onDelete('cascade');
            $table->foreignId('financial_transaction_id')->constrained('financial_transactions')->onDelete('restrict');
            
            // === VALOR ALOCADO (na moeda da transação) ===
            $table->bigInteger('allocated_amount')->comment('Valor alocado em centavos (na moeda da financial_transaction)');
            
            // === VARIAÇÃO CAMBIAL ===
            $table->bigInteger('gain_loss_on_exchange')->default(0)->comment('Ganho (+) ou Perda (-) cambial em centavos da moeda base');
            
            // === TIPO ===
            $table->enum('allocation_type', ['automatic', 'manual'])->default('manual');
            
            // === NOTAS ===
            $table->text('notes')->nullable();
            
            // === AUDITORIA ===
            $table->timestamp('created_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            // === INDEXES ===
            $table->index('financial_payment_id');
            $table->index('financial_transaction_id');
            
            // === UNIQUE CONSTRAINT ===
            // Previne duplicação de alocação
            $table->unique(['financial_payment_id', 'financial_transaction_id'], 'unique_payment_transaction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_payment_allocations');
    }
};
