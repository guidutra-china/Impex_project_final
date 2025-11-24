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
        Schema::create('financial_categories', function (Blueprint $table) {
            $table->id();
            
            // === IDENTIFICAÇÃO ===
            $table->string('name');
            $table->string('code', 20)->unique()->comment('Ex: COST-FIX-RENT');
            $table->text('description')->nullable();
            
            // === TIPO ===
            $table->enum('type', ['expense', 'revenue', 'exchange_variation'])->default('expense');
            
            // === HIERARQUIA ===
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->foreign('parent_id')->references('id')->on('financial_categories')->nullOnDelete();
            
            // === CONFIGURAÇÕES ===
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false)->comment('Categoria do sistema, não pode ser deletada');
            
            // === ORDEM ===
            $table->integer('sort_order')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            // === INDEXES ===
            $table->index('type');
            $table->index('parent_id');
            $table->index(['type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_categories');
    }
};
