<?php

namespace App\Filament\Resources\DocumentImports\Pages;

use App\Filament\Resources\DocumentImports\DocumentImportResource;
use App\Jobs\AnalyzeImportFileJob;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
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
        
        // Set initial status
        $data['status'] = 'pending';
        
        return $data;
    }
    
    /**
     * After creating the record, dispatch AI analysis job
     */
    protected function afterCreate(): void
    {
        // Dispatch AI analysis job
        AnalyzeImportFileJob::dispatch($this->record);
        
        // Show notification
        Notification::make()
            ->title('Import Created Successfully')
            ->body('AI analysis has been queued. You will be notified when it\'s ready.')
            ->success()
            ->send();
    }
    
    /**
     * Get redirect URL after creation
     */
    protected function getRedirectUrl(): string
    {
        // Redirect to view page to see analysis progress
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
