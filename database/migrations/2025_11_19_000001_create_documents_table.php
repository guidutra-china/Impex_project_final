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
            
            // === IDENTIFICAÇÃO ===
            $table->string('document_number', 100)->unique()->comment('Ex: DOC-2025-0001');
            $table->string('title');
            $table->text('description')->nullable();
            
            // === TIPO ===
            $table->enum('document_type', [
                'commercial_invoice',
                'proforma_invoice',
                'packing_list',
                'bill_of_lading',
                'certificate_of_origin',
                'quality_certificate',
                'insurance_certificate',
                'customs_declaration',
                'contract',
                'purchase_order',
                'sales_order',
                'other'
            ])->index();
            
            // === RELACIONAMENTO POLIMÓRFICO ===
            $table->string('related_type', 100)->nullable()->comment('PurchaseOrder, SalesOrder, Shipment, Supplier, Customer');
            $table->unsignedBigInteger('related_id')->nullable();
            $table->index(['related_type', 'related_id'], 'idx_related_composite');
            
            // === ARQUIVO ===
            $table->string('file_path', 500);
            $table->string('file_name');
            $table->string('safe_filename')->comment('UUID filename for security');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size')->comment('Size in bytes');
            
            // === STATUS ===
            $table->enum('status', ['draft', 'valid', 'expired', 'cancelled'])->default('valid')->index();
            
            // === DATAS ===
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable()->index();
            
            // === VISIBILIDADE ===
            $table->boolean('is_public')->default(false);
            $table->boolean('is_confidential')->default(false);
            
            // === NOTAS ===
            $table->text('notes')->nullable();
            
            // === AUDITORIA ===
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // === INDEXES ===
            $table->index('uploaded_by');
            $table->index('created_at');
            $table->index(['document_type', 'status'], 'idx_type_status');
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
