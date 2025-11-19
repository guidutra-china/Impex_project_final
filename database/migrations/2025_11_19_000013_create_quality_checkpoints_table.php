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
        Schema::create('quality_checkpoints', function (Blueprint $table) {
            $table->id();
            
            // === IDENTIFICAÇÃO ===
            $table->string('name');
            $table->text('description')->nullable();
            
            // === TIPO ===
            $table->enum('checkpoint_type', ['visual', 'measurement', 'functional', 'documentation']);
            
            // === CRITÉRIO ===
            $table->text('criterion')->comment('Critério de aceitação');
            
            // === APLICAÇÃO ===
            $table->enum('applies_to', ['all', 'product_category', 'specific_product'])->default('all');
            $table->foreignId('product_category_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('cascade');
            
            // === STATUS ===
            $table->boolean('is_active')->default(true);
            $table->boolean('is_mandatory')->default(false);
            
            // === ORDEM ===
            $table->integer('sort_order')->default(0);
            
            $table->timestamps();
            
            // === INDEXES ===
            $table->index('checkpoint_type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quality_checkpoints');
    }
};
