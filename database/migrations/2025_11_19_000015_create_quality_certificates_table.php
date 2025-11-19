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
        Schema::create('quality_certificates', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('quality_inspection_id')->constrained('quality_inspections')->onDelete('restrict');
            
            // === CERTIFICADO ===
            $table->string('certificate_number', 100)->unique();
            $table->string('certificate_type', 100)->comment('ISO, CE, FDA, etc');
            
            // === DATAS ===
            $table->date('issue_date');
            $table->date('expiry_date')->nullable();
            
            // === ARQUIVO ===
            $table->string('file_path', 500)->nullable();
            
            // === STATUS ===
            $table->enum('status', ['valid', 'expired', 'revoked'])->default('valid');
            
            // === NOTAS ===
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // === INDEXES ===
            $table->index('certificate_number');
            $table->index('quality_inspection_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quality_certificates');
    }
};
