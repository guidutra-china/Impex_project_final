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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_number', 100);
            $table->string('title', 255);
            $table->text('description');
            // TODO: `document_type` enum('commercial_invoice','proforma_invoice','packing_list','bill_of_lading','certificate_of_origin','quality_certificate','insurance_certificate','customs_declaration','contract','purchase_order','sales_order','other') COLLATE utf8mb4_unicode_ci NOT NULL
            $table->string('documentable_type', 100)->nullable();
            $table->bigInteger('documentable_id')->nullable();
            $table->string('related_type', 100)->nullable();
            $table->bigInteger('related_id')->nullable();
            $table->string('file_path', 500);
            $table->string('file_name', 255);
            $table->string('safe_filename', 255);
            $table->string('mime_type', 100);
            $table->bigInteger('file_size');
            // TODO: `status` enum('draft','valid','expired','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'valid'
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->integer('is_public');
            $table->integer('is_confidential');
            $table->text('notes');
            $table->bigInteger('uploaded_by')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
