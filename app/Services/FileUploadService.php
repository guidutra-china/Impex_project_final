<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

/**
 * FileUploadService
 * 
 * Provides secure file upload handling with validation,
 * sanitization, and safe storage.
 * 
 * Security Features:
 * - MIME type validation
 * - File size limits
 * - Path traversal prevention
 * - Dangerous extension blocking
 * - Safe filename generation
 * - Private storage by default
 */
class FileUploadService
{
    /**
     * Allowed MIME types for different file categories
     */
    private const ALLOWED_MIME_TYPES = [
        'documents' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ],
        'images' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
        ],
        'spreadsheets' => [
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/csv',
        ],
    ];

    /**
     * Dangerous file extensions that should never be allowed
     */
    private const DANGEROUS_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'pht',
        'exe', 'com', 'bat', 'cmd', 'scr', 'vbs', 'js', 'jar',
        'html', 'htm', 'asp', 'aspx', 'jsp', 'jspx',
        'sh', 'bash', 'zsh', 'ksh', 'csh',
        'app', 'dmg', 'pkg', 'deb', 'rpm',
    ];

    /**
     * Maximum file sizes (in bytes)
     */
    private const MAX_FILE_SIZES = [
        'documents' => 10 * 1024 * 1024, // 10 MB
        'images' => 5 * 1024 * 1024,     // 5 MB
        'spreadsheets' => 20 * 1024 * 1024, // 20 MB
    ];

    /**
     * Upload a file with validation
     *
     * @param UploadedFile $file
     * @param string $category One of: documents, images, spreadsheets
     * @param string $storagePath Path within storage (e.g., 'imports/rfq')
     * @return array ['success' => bool, 'path' => string|null, 'error' => string|null]
     */
    public function upload(
        UploadedFile $file,
        string $category = 'documents',
        string $storagePath = 'uploads'
    ): array {
        try {
            // Validate file
            $validation = $this->validateFile($file, $category);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'path' => null,
                    'error' => $validation['error'],
                ];
            }

            // Generate safe filename
            $safeFilename = $this->generateSafeFilename($file);

            // Store file in private storage
            $path = Storage::disk('private')->putFileAs(
                $storagePath,
                $file,
                $safeFilename
            );

            if (!$path) {
                throw new FileException('Failed to store file');
            }

            Log::info('File uploaded successfully', [
                'original_name' => $file->getClientOriginalName(),
                'safe_name' => $safeFilename,
                'path' => $path,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ]);

            return [
                'success' => true,
                'path' => $path,
                'error' => null,
            ];

        } catch (\Exception $e) {
            Log::error('File upload failed', [
                'original_name' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'path' => null,
                'error' => 'File upload failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Validate uploaded file
     *
     * @return array ['valid' => bool, 'error' => string|null]
     */
    private function validateFile(UploadedFile $file, string $category): array
    {
        // Check if category exists
        if (!isset(self::ALLOWED_MIME_TYPES[$category])) {
            return [
                'valid' => false,
                'error' => "Invalid file category: {$category}",
            ];
        }

        // Check file size
        $maxSize = self::MAX_FILE_SIZES[$category] ?? 10 * 1024 * 1024;
        if ($file->getSize() > $maxSize) {
            return [
                'valid' => false,
                'error' => "File exceeds maximum size of " . ($maxSize / 1024 / 1024) . " MB",
            ];
        }

        // Check MIME type
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES[$category])) {
            return [
                'valid' => false,
                'error' => "File type {$mimeType} is not allowed for {$category}",
            ];
        }

        // Check extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (in_array($extension, self::DANGEROUS_EXTENSIONS)) {
            return [
                'valid' => false,
                'error' => "File extension .{$extension} is not allowed",
            ];
        }

        // Check for path traversal in original filename
        $originalName = $file->getClientOriginalName();
        if (str_contains($originalName, '..') || str_contains($originalName, '/') || str_contains($originalName, '\\')) {
            return [
                'valid' => false,
                'error' => 'Invalid filename detected',
            ];
        }

        return [
            'valid' => true,
            'error' => null,
        ];
    }

    /**
     * Generate a safe filename
     */
    private function generateSafeFilename(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $uuid = Str::uuid()->toString();
        return "{$uuid}.{$extension}";
    }

    /**
     * Delete a file
     */
    public function delete(string $path): bool
    {
        try {
            if (Storage::disk('private')->exists($path)) {
                Storage::disk('private')->delete($path);
                Log::info('File deleted', ['path' => $path]);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            Log::error('File deletion failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get file download response
     */
    public function download(string $path, string $originalName = null)
    {
        try {
            if (!Storage::disk('private')->exists($path)) {
                throw new FileException('File not found');
            }

            return Storage::disk('private')->download(
                $path,
                $originalName ?? basename($path)
            );
        } catch (\Exception $e) {
            Log::error('File download failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
