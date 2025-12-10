<?php

namespace App\Filament\Resources\DocumentImports;

use App\Filament\Resources\DocumentImports\Pages\ListDocumentImports;
use App\Filament\Resources\DocumentImports\Pages\ViewDocumentImport;
use App\Filament\Resources\DocumentImports\Pages\CreateDocumentImport;
use App\Filament\Resources\DocumentImports\Tables\DocumentImportTable;
use App\Models\ImportHistory;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class DocumentImportResource extends Resource
{
    protected static ?string $model = ImportHistory::class;

    protected static ?string $navigationGroup = 'System';

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?int $navigationSort = 100;

    protected static ?string $navigationLabel = 'Document Imports';

    protected static ?string $modelLabel = 'Document Import';

    protected static ?string $pluralModelLabel = 'Document Imports';

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.system');
    }

    public static function getModelLabel(): string
    {
        return 'Document Import';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Document Imports';
    }

    public static function getNavigationLabel(): string
    {
        return 'Document Imports';
    }

    public static function form(Schema $schema): Schema
    {
        // No form needed - imports are created via action
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return DocumentImportTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocumentImports::route('/'),
            'view' => ViewDocumentImport::route('/{record}'),
            'create' => CreateDocumentImport::route('/create'),
        ];
    }

    public static function canCreate(): bool
    {
        return true;
    }

    public static function canEdit($record): bool
    {
        return false; // Imports cannot be edited
    }

    public static function canDelete($record): bool
    {
        return $record->isCompleted() || $record->isFailed();
    }
}
