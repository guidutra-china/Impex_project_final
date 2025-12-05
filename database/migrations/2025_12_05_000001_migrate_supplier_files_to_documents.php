<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate data from supplier_files to documents
        if (Schema::hasTable('supplier_files')) {
            $supplierFiles = DB::table('supplier_files')->get();
            
            foreach ($supplierFiles as $file) {
                // Determine document_type based on file_type
                $documentType = match($file->file_type) {
                    'photo' => 'other',
                    'document' => 'other',
                    'certificate' => 'quality_certificate',
                    'contract' => 'contract',
                    default => 'other'
                };
                
                // Generate document number
                $documentNumber = 'SUP-' . str_pad($file->id, 6, '0', STR_PAD_LEFT);
                
                // Insert into documents table
                DB::table('documents')->insert([
                    'document_number' => $documentNumber,
                    'title' => $file->original_filename ?? 'Supplier File',
                    'description' => $file->description,
                    'document_type' => $documentType,
                    'documentable_type' => 'App\\Models\\Supplier',
                    'documentable_id' => $file->supplier_id,
                    'related_type' => null,
                    'related_id' => null,
                    'file_path' => $file->file_path,
                    'file_name' => $file->original_filename ?? 'unknown',
                    'safe_filename' => Str::slug(pathinfo($file->original_filename ?? 'file', PATHINFO_FILENAME)) . '_' . Str::random(8),
                    'mime_type' => $file->mime_type ?? 'application/octet-stream',
                    'file_size' => $file->file_size ?? 0,
                    'status' => 'valid',
                    'issue_date' => $file->date_uploaded,
                    'is_public' => false,
                    'is_confidential' => false,
                    'uploaded_by' => null, // No user tracking in old system
                    'created_at' => $file->created_at ?? now(),
                    'updated_at' => $file->updated_at ?? now(),
                ]);
            }
            
            // Log migration
            \Log::info('Migrated ' . $supplierFiles->count() . ' supplier files to documents table');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove migrated documents
        DB::table('documents')
            ->where('documentable_type', 'App\\Models\\Supplier')
            ->where('document_number', 'like', 'SUP-%')
            ->delete();
    }
};
