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
        Schema::create('quality_inspections', function (Blueprint $table) {
            $table->id();
            
            // === IDENTIFICAÇÃO ===
            $table->string('inspection_number', 50)->unique()->comment('Ex: QC-2025-0001');
            
            // === RELACIONAMENTO POLIMÓRFICO ===
            $table->string('inspectable_type', 100)->comment('PurchaseOrder, SalesOrder, Product');
            $table->unsignedBigInteger('inspectable_id');
            
            // === TIPO ===
            $table->enum('inspection_type', [
                'incoming',         // Inspeção de recebimento
                'in_process',       // Inspeção em processo
                'final',            // Inspeção final
                'random',           // Inspeção aleatória
                'customer_return'   // Inspeção de devolução
            ]);
            
            // === STATUS ===
            $table->enum('status', ['pending', 'in_progress', 'completed', 'failed', 'cancelled'])->default('pending');
            
            // === RESULTADO ===
            $table->enum('result', ['passed', 'failed', 'conditional'])->nullable();
            
            // === DATAS ===
            $table->date('inspection_date');
            $table->date('completed_date')->nullable();
            
            // === INSPETOR ===
            $table->foreignId('inspector_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('inspector_name')->nullable();
            
            // === NOTAS ===
            $table->text('notes')->nullable();
            $table->text('failure_reason')->nullable();
            $table->text('corrective_action')->nullable();
            
            // === AUDITORIA ===
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // === INDEXES ===
            $table->index('inspection_number');
            $table->index(['inspectable_type', 'inspectable_id'], 'idx_inspectable');
            $table->index('status');
            $table->index('result');
            $table->index('inspection_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quality_inspections');
    }
};
