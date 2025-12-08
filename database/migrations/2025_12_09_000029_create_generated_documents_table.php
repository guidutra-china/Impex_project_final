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
        Schema::create('generated_documents', function (Blueprint $table) {
            $table->id();
            $table->string('documentable_type', 255);
            $table->bigInteger('documentable_id');
            $table->string('document_type', 255);
            $table->string('document_number', 255)->nullable();
            $table->string('format', 255);
            $table->string('filename', 255);
            $table->string('file_path', 255);
            $table->bigInteger('file_size')->nullable();
            $table->integer('version');
            $table->integer('revision_number')->nullable();
            $table->bigInteger('generated_by')->nullable();
            $table->timestamp('generated_at');
            $table->string('generated_from_ip', 45)->nullable();
            // TODO: `metadata` json DEFAULT NULL
            $table->text('notes');
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generated_documents');
    }
};
