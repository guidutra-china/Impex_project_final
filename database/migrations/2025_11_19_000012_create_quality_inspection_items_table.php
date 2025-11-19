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
        Schema::create('quality_inspection_items', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('quality_inspection_id')->constrained('quality_inspections')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            
            // === QUANTIDADE ===
            $table->integer('quantity_inspected');
            $table->integer('quantity_passed')->default(0);
            $table->integer('quantity_failed')->default(0);
            
            // === RESULTADO ===
            $table->enum('result', ['passed', 'failed', 'conditional'])->nullable();
            
            // === NOTAS ===
            $table->text('defects_found')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // === INDEXES ===
            $table->index('quality_inspection_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quality_inspection_items');
    }
};
