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
        Schema::create('supplier_performance_reviews', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('restrict');
            
            // === PERÍODO ===
            $table->date('review_date');
            $table->date('review_period_start');
            $table->date('review_period_end');
            
            // === AVALIAÇÃO ===
            $table->decimal('delivery_score', 5, 2)->comment('0-100');
            $table->decimal('quality_score', 5, 2)->comment('0-100');
            $table->decimal('pricing_score', 5, 2)->comment('0-100');
            $table->decimal('communication_score', 5, 2)->comment('0-100');
            $table->decimal('overall_score', 5, 2)->comment('0-100');
            
            // === CLASSIFICAÇÃO ===
            $table->enum('rating', ['A', 'B', 'C', 'D', 'F']);
            
            // === COMENTÁRIOS ===
            $table->text('strengths')->nullable();
            $table->text('weaknesses')->nullable();
            $table->text('recommendations')->nullable();
            
            // === DECISÃO ===
            $table->enum('decision', ['continue', 'monitor', 'improve_required', 'discontinue']);
            
            // === REVISOR ===
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            
            // === INDEXES ===
            $table->index('supplier_id');
            $table->index('review_date');
            $table->index('rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_performance_reviews');
    }
};
