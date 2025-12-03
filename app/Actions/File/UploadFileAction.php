<?php

namespace App\Actions\File;

use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/**
 * UploadFileAction
 * 
 * Handles secure file uploads with validation and storage.
 * This action encapsulates the logic for validating and storing files
 * in the appropriate location with security checks.
 * 
 * @example
 * $action = new UploadFileAction(new FileUploadService());
 * $result = $action->execute($file, 'documents', 'test');
 */
class UploadFileAction
{
    /**
     * Create a new action instance
     */
    public function __construct(
        private FileUploadService $uploadService
    ) {
    }

    /**
     * Execute the file upload action
     * 
     * @param UploadedFile $file The file to upload
     * @param string $category The category/folder for the file
     * @param string $prefix Optional prefix for the filename
     * @return array Result with success status and file path
     */
    public function execute(UploadedFile $file, string $category, string $prefix = ''): array
    {
        return $this->uploadService->upload($file, $category, $prefix);
    }

    /**
     * Handle the file upload with validation and logging
     * 
     * @param UploadedFile $file
     * @param string $category
     * @param string $prefix
     * @param array $options Additional options
     * @return array
     */
    public function handle(UploadedFile $file, string $category, string $prefix = '', array $options = []): array
    {
        // Validate file
        if (!$file->isValid()) {
            $error = 'File upload failed: ' . $file->getErrorMessage();
            Log::warning('Invalid file upload', [
                'category' => $category,
                'error' => $error,
            ]);
            return [
                'success' => false,
                'error' => $error,
                'path' => null,
            ];
        }

        // Log the upload attempt
        Log::info('Starting file upload', [
            'filename' => $file->getClientOriginalName(),
            'category' => $category,
            'size' => $file->getSize(),
            'mime' => $file->getMimeType(),
        ]);

        try {
            $result = $this->execute($file, $category, $prefix);

            if ($result['success']) {
                Log::info('File upload completed', [
                    'filename' => $file->getClientOriginalName(),
                    'category' => $category,
                    'path' => $result['path'],
                ]);
            } else {
                Log::warning('File upload failed', [
                    'filename' => $file->getClientOriginalName(),
                    'category' => $category,
                    'error' => $result['error'],
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('File upload error', [
                'filename' => $file->getClientOriginalName(),
                'category' => $category,
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'error' => 'Upload failed: ' . $e->getMessage(),
                'path' => null,
            ];
        }
    }

    /**
     * Delete a file
     * 
     * @param string $path The file path to delete
     * @return bool Success status
     */
    public function delete(string $path): bool
    {
        return $this->uploadService->delete($path);
    }

    /**
     * Validate a file before upload
     * 
     * @param UploadedFile $file
     * @param string $category
     * @return array Validation result with success and error message
     */
    public function validate(UploadedFile $file, string $category): array
    {
        if (!$file->isValid()) {
            return [
                'success' => false,
                'error' => 'File is not valid: ' . $file->getErrorMessage(),
            ];
        }

        // Additional validation can be added here
        // For example, checking file size, MIME type, etc.

        return [
            'success' => true,
            'error' => null,
        ];
    }
}
