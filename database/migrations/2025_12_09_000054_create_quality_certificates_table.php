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
            $table->bigInteger('quality_inspection_id');
            $table->string('certificate_number', 100);
            $table->string('certificate_type', 100);
            $table->date('issue_date');
            $table->date('expiry_date')->nullable();
            $table->string('file_path', 500)->nullable();
            // TODO: `status` enum('valid','expired','revoked') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'valid'
            $table->text('notes');
            $table->timestamps();
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
