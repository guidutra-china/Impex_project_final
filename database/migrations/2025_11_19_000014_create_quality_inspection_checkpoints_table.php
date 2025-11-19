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
        Schema::create('quality_inspection_checkpoints', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('quality_inspection_id')->constrained('quality_inspections')->onDelete('cascade');
            $table->foreignId('quality_checkpoint_id')->constrained('quality_checkpoints')->onDelete('restrict');
            
            // === RESULTADO ===
            $table->enum('result', ['pass', 'fail', 'n/a']);
            
            // === MEDIÇÃO ===
            $table->string('measured_value')->nullable();
            $table->string('expected_value')->nullable();
            
            // === NOTAS ===
            $table->text('notes')->nullable();
            
            // === INSPETOR ===
            $table->foreignId('checked_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('checked_at')->nullable();
            
            $table->timestamp('created_at');
            
            // === INDEXES ===
            $table->index('quality_inspection_id');
            $table->index('quality_checkpoint_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quality_inspection_checkpoints');
    }
};
