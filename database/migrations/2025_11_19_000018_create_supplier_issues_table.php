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
        Schema::create('supplier_issues', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('restrict');
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->onDelete('set null');
            
            // === TIPO ===
            $table->enum('issue_type', [
                'late_delivery',
                'quality_problem',
                'wrong_quantity',
                'wrong_product',
                'damaged_goods',
                'pricing_error',
                'communication_issue',
                'other'
            ]);
            
            // === SEVERIDADE ===
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            
            // === STATUS ===
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');
            
            // === DESCRIÇÃO ===
            $table->text('description');
            
            // === RESOLUÇÃO ===
            $table->text('resolution')->nullable();
            $table->date('resolution_date')->nullable();
            
            // === IMPACTO FINANCEIRO ===
            $table->bigInteger('financial_impact')->default(0)->comment('Custo do problema em cents');
            
            // === DATAS ===
            $table->date('reported_date');
            
            // === RESPONSÁVEL ===
            $table->foreignId('reported_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            
            // === INDEXES ===
            $table->index('supplier_id');
            $table->index('purchase_order_id');
            $table->index('issue_type');
            $table->index('severity');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_issues');
    }
};
