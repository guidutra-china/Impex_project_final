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
        Schema::create('supplier_performance_metrics', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            
            // === PERÍODO ===
            $table->integer('period_year');
            $table->integer('period_month');
            
            // === MÉTRICAS DE ENTREGA ===
            $table->integer('total_orders')->default(0);
            $table->integer('on_time_deliveries')->default(0);
            $table->integer('late_deliveries')->default(0);
            $table->decimal('average_delay_days', 5, 2)->default(0);
            
            // === MÉTRICAS DE QUALIDADE ===
            $table->integer('total_inspections')->default(0);
            $table->integer('passed_inspections')->default(0);
            $table->integer('failed_inspections')->default(0);
            $table->decimal('quality_score', 5, 2)->default(0)->comment('Percentual de aprovação');
            
            // === MÉTRICAS FINANCEIRAS ===
            $table->bigInteger('total_purchase_value')->default(0)->comment('In cents');
            $table->bigInteger('total_orders_value')->default(0)->comment('In cents');
            $table->bigInteger('average_order_value')->default(0)->comment('In cents');
            
            // === MÉTRICAS DE COMUNICAÇÃO ===
            $table->decimal('response_time_hours', 10, 2)->default(0);
            $table->decimal('communication_score', 5, 2)->default(0);
            
            // === SCORE GERAL ===
            $table->decimal('overall_score', 5, 2)->default(0)->comment('0-100');
            $table->enum('rating', ['excellent', 'good', 'average', 'poor', 'unacceptable'])->nullable();
            
            // === NOTAS ===
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // === INDEXES ===
            $table->unique(['supplier_id', 'period_year', 'period_month'], 'unique_period');
            $table->index('supplier_id');
            $table->index(['period_year', 'period_month'], 'idx_period');
            $table->index('overall_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_performance_metrics');
    }
};
