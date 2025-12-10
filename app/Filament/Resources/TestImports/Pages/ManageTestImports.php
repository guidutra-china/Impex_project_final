<?php

namespace App\Filament\Resources\TestImports\Pages;

use App\Filament\Resources\TestImports\TestImportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTestImports extends ManageRecords
{
    protected static string $resource = TestImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
