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
        Schema::create('supplier_files', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('supplier_id');
            // TODO: `file_type` enum('photo','document') COLLATE utf8mb4_unicode_ci NOT NULL
            $table->string('file_path', 255);
            $table->string('original_filename', 255);
            $table->text('description');
            $table->date('date_uploaded');
            $table->integer('file_size')->nullable();
            $table->string('mime_type', 255)->nullable();
            $table->integer('sort_order');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_files');
    }
};
