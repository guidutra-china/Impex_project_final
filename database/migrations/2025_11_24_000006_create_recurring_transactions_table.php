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
            
            // === IDENTIFICAÇÃO ===
            $table->string('name');
            $table->text('description')->nullable();
            
            // === TIPO ===
            $table->enum('type', ['payable', 'receivable'])->comment('payable = Despesa Recorrente, receivable = Receita Recorrente');
            
            // === CATEGORIA ===
            $table->foreignId('financial_category_id')->constrained('financial_categories')->onDelete('restrict');
            
            // === VALORES ===
            $table->bigInteger('amount')->comment('Valor em centavos');
            $table->foreignId('currency_id')->constrained('currencies')->onDelete('restrict');
            
            // === RECORRÊNCIA ===
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])->default('monthly');
            $table->integer('interval')->default(1)->comment('Ex: a cada 2 meses = interval 2 com frequency monthly');
            $table->integer('day_of_month')->nullable()->comment('Dia do mês (1-31) para recorrências mensais');
            $table->integer('day_of_week')->nullable()->comment('Dia da semana (0-6) para recorrências semanais');
            
            // === PERÍODO ===
            $table->date('start_date');
            $table->date('end_date')->nullable()->comment('Null = sem data de término');
            $table->date('next_due_date')->comment('Próxima data de geração');
            
            // === RELACIONAMENTO (opcional) ===
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('restrict');
            $table->foreignId('client_id')->nullable()->constrained('clients')->onDelete('restrict');
            
            // === CONFIGURAÇÕES ===
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_generate')->default(true)->comment('Gerar automaticamente ou apenas alertar');
            $table->integer('days_before_due')->default(0)->comment('Quantos dias antes do vencimento gerar a transação');
            
            // === ÚLTIMA GERAÇÃO ===
            $table->date('last_generated_date')->nullable();
            $table->foreignId('last_generated_transaction_id')->nullable()->constrained('financial_transactions')->nullOnDelete();
            
            // === NOTAS ===
            $table->text('notes')->nullable();
            
            // === AUDITORIA ===
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            // === INDEXES ===
            $table->index('type');
            $table->index('frequency');
            $table->index('next_due_date');
            $table->index('is_active');
            $table->index(['is_active', 'next_due_date']);
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
