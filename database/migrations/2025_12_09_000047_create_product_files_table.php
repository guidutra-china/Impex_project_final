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
        Schema::create('product_files', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('product_id');
            // TODO: `file_type` enum('photo','document') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'photo'
            $table->string('file_path', 255);
            $table->string('original_filename', 255);
            $table->string('mime_type', 255)->nullable();
            $table->integer('file_size')->nullable();
            $table->text('description');
            $table->date('date_uploaded')->nullable();
            $table->integer('sort_order');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_files');
    }
};
