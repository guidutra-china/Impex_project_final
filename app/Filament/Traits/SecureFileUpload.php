<?php

namespace App\Filament\Traits;

use App\Services\FileUploadService;
use Illuminate\Support\Facades\Storage;

/**
 * SecureFileUpload Trait
 * 
 * Provides standardized secure file upload methods for Filament resources.
 * Ensures all uploads go through FileUploadService validation.
 */
trait SecureFileUpload
{
    /**
     * Create a secure file upload callback for private storage
     * 
     * @param string $category Category for FileUploadService (documents, images, spreadsheets)
     * @param string $storagePath Path within private storage
     * @return \Closure
     */
    protected static function secureUploadPrivate(string $category, string $storagePath): \Closure
    {
        return function ($file) use ($category, $storagePath) {
            $uploadService = app(FileUploadService::class);
            
            $result = $uploadService->upload($file, $category, $storagePath);
            
            if (!$result['success']) {
                throw new \Exception($result['error']);
            }
            
            return $result['path'];
        };
    }

    /**
     * Create a secure file upload callback for public storage
     * 
     * Validates through FileUploadService then moves to public storage
     * 
     * @param string $category Category for FileUploadService (documents, images, spreadsheets)
     * @param string $publicPath Path within public storage
     * @return \Closure
     */
    protected static function secureUploadPublic(string $category, string $publicPath): \Closure
    {
        return function ($file) use ($category, $publicPath) {
            $uploadService = app(FileUploadService::class);
            
            // First validate through security service
            $result = $uploadService->upload($file, $category, 'temp');
            
            if (!$result['success']) {
                throw new \Exception($result['error']);
            }
            
            // Copy from private to public storage
            $privatePath = $result['path'];
            $filename = basename($privatePath);
            $finalPath = $publicPath . '/' . $filename;
            
            Storage::disk('public')->put(
                $finalPath,
                Storage::disk('private')->get($privatePath)
            );
            
            // Clean up private storage
            Storage::disk('private')->delete($privatePath);
            
            \Log::info('File uploaded securely to public storage', [
                'path' => $finalPath,
                'category' => $category,
                'original_name' => $file->getClientOriginalName(),
            ]);
            
            return $finalPath;
        };
    }

    /**
     * Get accepted file types for a category
     * 
     * @param string $category
     * @return array
     */
    protected static function getAcceptedFileTypes(string $category): array
    {
        return match ($category) {
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
            default => [],
        };
    }

    /**
     * Get max file size for a category (in KB)
     * 
     * @param string $category
     * @return int
     */
    protected static function getMaxFileSize(string $category): int
    {
        return match ($category) {
            'documents' => 10240,    // 10 MB
            'images' => 5120,        // 5 MB
            'spreadsheets' => 20480, // 20 MB
            default => 10240,
        };
    }
}
