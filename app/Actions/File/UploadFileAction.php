<?php

namespace App\Actions\File;

use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/**
 * UploadFileAction
 * 
 * Business logic action for secure file uploads.
 * This action encapsulates the core business logic for file validation
 * and storage, separate from UI concerns. It can be used in multiple contexts:
 * - Filament Resources (via Action::make())
 * - Controllers
 * - Jobs/Queues
 * - API endpoints
 * - Livewire Components
 * 
 * Filament V4 Pattern:
 * Actions in Filament V4 are primarily UI-centric, but this class
 * represents the underlying business logic that can be invoked from
 * Filament Actions or other contexts.
 * 
 * @example
 * // In a Filament Resource or Component:
 * $action = app(UploadFileAction::class);
 * $result = $action->execute($file, 'documents', 'prefix');
 * 
 * // Or via Filament Action:
 * Action::make('upload')
 *     ->action(fn (UploadFileAction $action, UploadedFile $file) =>
 *         $action->execute($file, 'documents')
 *     )
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
     * Execute the file upload
     * 
     * This is the main entry point for the action. It validates and stores
     * the file in the appropriate location.
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
     * Execute with validation
     * 
     * Use this method when you want to perform validation before upload.
     * This is useful when called from Filament Actions where you might have
     * additional context.
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
     * Convenience method to delete a previously uploaded file.
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
     * Convenience method to validate a file without uploading it.
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

        return [
            'success' => true,
            'error' => null,
        ];
    }
}
