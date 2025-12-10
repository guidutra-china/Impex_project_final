<?php

namespace App\Filament\Resources\DocumentImports\Pages;

use App\Filament\Resources\DocumentImports\DocumentImportResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CreateDocumentImport extends CreateRecord
{
    protected static string $resource = DocumentImportResource::class;
    
    /**
     * Mutate form data before creating the record
     * Automatically set the user_id and extract file metadata
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set authenticated user
        $data['user_id'] = Auth::id();
        
        // Extract file information from uploaded file
        if (isset($data['file'])) {
            $filePath = $data['file'];
            
            // Get file information from storage
            $disk = Storage::disk('private');
            
            $data['file_path'] = $filePath;
            $data['file_name'] = basename($filePath);
            $data['file_type'] = pathinfo($filePath, PATHINFO_EXTENSION);
            $data['file_size'] = $disk->size($filePath);
            
            // Remove the temporary 'file' field
            unset($data['file']);
        }
        
        return $data;
    }
}
