<?php

namespace App\Filament\Resources\QualityInspections\Pages;

use App\Filament\Resources\QualityInspections\QualityInspectionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditQualityInspection extends EditRecord
{
    protected static string $resource = QualityInspectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
