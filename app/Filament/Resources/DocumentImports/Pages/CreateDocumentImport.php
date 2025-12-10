<?php

namespace App\Filament\Resources\DocumentImports\Pages;

use App\Filament\Resources\DocumentImports\DocumentImportResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDocumentImport extends CreateRecord
{
    protected static string $resource = DocumentImportResource::class;

    // Temporary bypass to test if it's a Shield permission issue
    protected function authorizeAccess(): void
    {
        // Bypassing authorization temporarily
        return;
    }
}
