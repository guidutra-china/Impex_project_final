<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;

class DocumentService
{
    /**
     * Allowed MIME types for security
     */
    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    /**
     * Max file size in bytes (10MB)
     */
    private const MAX_FILE_SIZE = 10 * 1024 * 1024;

    /**
     * Create a new document with file upload
     *
     * @param array $data
     * @param UploadedFile $file
     * @return Document
     * @throws \Exception
     */
    public function createDocument(array $data, UploadedFile $file): Document
    {
        // Validate file
        $this->validateFile($file);

        return DB::transaction(function () use ($data, $file) {
            // Generate safe filename
            $safeFilename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            
            // Store file
            $path = $file->storeAs('documents', $safeFilename, 'private');

            // Generate document number if not provided
            if (!isset($data['document_number'])) {
                $data['document_number'] = $this->generateDocumentNumber();
            }

            // Create document
            $document = Document::create([
                ...$data,
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'safe_filename' => $safeFilename,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_by' => auth()->id(),
            ]);

            return $document;
        });
    }

    /**
     * Create a new version of existing document
     *
     * @param Document $document
     * @param UploadedFile $file
     * @param string|null $changeNotes
     * @return DocumentVersion
     * @throws \Exception
     */
    public function createVersion(Document $document, UploadedFile $file, ?string $changeNotes = null): DocumentVersion
    {
        $this->validateFile($file);

        return DB::transaction(function () use ($document, $file, $changeNotes) {
            // Get next version number
            $nextVersion = $document->versions()->max('version_number') + 1;

            // Generate safe filename
            $safeFilename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            
            // Store file
            $path = $file->storeAs('documents/versions', $safeFilename, 'private');

            // Create version
            $version = DocumentVersion::create([
                'document_id' => $document->id,
                'version_number' => $nextVersion,
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'change_notes' => $changeNotes,
                'uploaded_by' => auth()->id(),
            ]);

            // Update document's main file
            $document->update([
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'safe_filename' => $safeFilename,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ]);

            return $version;
        });
    }

    /**
     * Delete document and its files
     *
     * @param Document $document
     * @return bool
     */
    public function deleteDocument(Document $document): bool
    {
        return DB::transaction(function () use ($document) {
            // Delete main file
            if (Storage::disk('private')->exists($document->file_path)) {
                Storage::disk('private')->delete($document->file_path);
            }

            // Delete version files
            foreach ($document->versions as $version) {
                if (Storage::disk('private')->exists($version->file_path)) {
                    Storage::disk('private')->delete($version->file_path);
                }
            }

            // Delete document (cascade will delete versions)
            return $document->delete();
        });
    }

    /**
     * Mark expired documents
     *
     * @return int Number of documents marked as expired
     */
    public function markExpiredDocuments(): int
    {
        return Document::where('status', 'valid')
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now())
            ->update(['status' => 'expired']);
    }

    /**
     * Get documents expiring soon
     *
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getExpiringSoon(int $days = 30)
    {
        return Document::expiringSoon($days)->get();
    }

    /**
     * Validate uploaded file
     *
     * @param UploadedFile $file
     * @return void
     * @throws \Exception
     */
    private function validateFile(UploadedFile $file): void
    {
        // Check file size
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new \Exception('File size exceeds maximum allowed size of 10MB');
        }

        // Check MIME type
        if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
            throw new \Exception('File type not allowed. Allowed types: PDF, Images, Word, Excel');
        }

        // Check if file is valid
        if (!$file->isValid()) {
            throw new \Exception('Invalid file upload');
        }
    }

    /**
     * Generate unique document number
     *
     * @return string
     */
    private function generateDocumentNumber(): string
    {
        $year = date('Y');
        $lastDocument = Document::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastDocument ? (int) substr($lastDocument->document_number, -4) + 1 : 1;

        return sprintf('DOC-%s-%04d', $year, $nextNumber);
    }
}
