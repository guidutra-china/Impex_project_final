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
        Schema::create('import_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // File information
            $table->string('file_name');
            $table->string('file_type'); // excel, pdf
            $table->string('file_path')->nullable();
            $table->integer('file_size')->nullable(); // in bytes
            
            // Import type and target
            $table->string('import_type'); // products, suppliers, clients, quotes, etc.
            $table->string('document_type')->nullable(); // Proforma Invoice, Catalog, etc. (from AI)
            
            // AI Analysis
            $table->json('ai_analysis')->nullable();
            $table->json('column_mapping')->nullable();
            
            // Supplier/Source information
            $table->string('supplier_name')->nullable();
            $table->string('supplier_email')->nullable();
            
            // Import results
            $table->enum('status', ['pending', 'analyzing', 'ready', 'importing', 'completed', 'failed'])->default('pending');
            $table->integer('total_rows')->default(0);
            $table->integer('success_count')->default(0);
            $table->integer('updated_count')->default(0);
            $table->integer('skipped_count')->default(0);
            $table->integer('error_count')->default(0);
            $table->integer('warning_count')->default(0);
            
            // Detailed results
            $table->json('errors')->nullable();
            $table->json('warnings')->nullable();
            $table->text('result_message')->nullable();
            
            // Timestamps
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('user_id');
            $table->index('import_type');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_histories');
    }
};
