<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_documents', function (Blueprint $table) {
            $table->id();
            
            // Polymorphic relationship to any document type
            $table->string('documentable_type'); // Order, SupplierQuote, ProformaInvoice, etc.
            $table->unsignedBigInteger('documentable_id');
            $table->index(['documentable_type', 'documentable_id']);
            
            // Document metadata
            $table->string('document_type'); // rfq, supplier_quote, proforma_invoice, purchase_order, commercial_invoice
            $table->string('document_number')->nullable(); // RFQ-2025-0001, PI-2025-0001, etc.
            $table->string('format'); // pdf, excel, csv
            $table->string('filename'); // original filename
            $table->string('file_path'); // storage path
            $table->unsignedBigInteger('file_size')->nullable(); // in bytes
            
            // Version control
            $table->integer('version')->default(1);
            $table->integer('revision_number')->nullable(); // for documents that have revisions
            
            // Audit trail
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at');
            $table->ipAddress('generated_from_ip')->nullable();
            
            // Additional metadata
            $table->json('metadata')->nullable(); // additional info like template used, options, etc.
            $table->text('notes')->nullable();
            
            // Soft deletes for document retention
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_documents');
    }
};
