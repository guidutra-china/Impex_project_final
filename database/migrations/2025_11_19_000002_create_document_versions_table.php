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
        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
            
            // === VERSÃO ===
            $table->unsignedInteger('version_number');
            
            // === ARQUIVO ===
            $table->string('file_path', 500);
            $table->string('file_name');
            $table->unsignedBigInteger('file_size');
            
            // === MUDANÇAS ===
            $table->text('change_notes')->nullable();
            
            // === AUDITORIA ===
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('created_at');
            
            // === INDEXES ===
            $table->index('document_id');
            $table->index('version_number');
            $table->unique(['document_id', 'version_number'], 'unique_document_version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_versions');
    }
};
