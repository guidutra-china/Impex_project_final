<?php

namespace App\Filament\Resources\DocumentImports\Pages;

use App\Filament\Resources\DocumentImports\DocumentImportResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateDocumentImport extends CreateRecord
{
    protected static string $resource = DocumentImportResource::class;
    
    /**
     * Mutate form data before creating the record
     * Automatically set the user_id to the authenticated user
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        
        return $data;
    }
}
