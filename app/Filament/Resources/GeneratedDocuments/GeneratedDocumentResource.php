<?php

namespace App\Filament\Resources\GeneratedDocuments;

use App\Filament\Resources\GeneratedDocuments\Tables\GeneratedDocumentsTable;
use App\Models\GeneratedDocument;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use BackedEnum;
use UnitEnum;

class GeneratedDocumentResource extends Resource
{
    protected static ?string $model = GeneratedDocument::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-duplicate';

    protected static UnitEnum|string|null $navigationGroup = 'Documents';

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.documents');
    }

    public static function getModelLabel(): string
    {
        return __('navigation.document_history');
    }

    public static function getPluralModelLabel(): string
    {
        return __('navigation.document_history');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.document_history');
    }

    protected static ?string $navigationLabel = 'Document History';

    protected static ?string $modelLabel = 'Document History';

    protected static ?string $pluralModelLabel = 'Document History';

    protected static ?int $navigationSort = 10;

    public static function table(Table $table): Table
    {
        return GeneratedDocumentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\GeneratedDocuments\Pages\ListGeneratedDocuments::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
