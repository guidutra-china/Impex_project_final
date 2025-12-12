<?php

namespace App\Filament\Resources\DocumentImports;

use App\Filament\Resources\DocumentImports\Pages\ConfigureMapping;
use App\Filament\Resources\DocumentImports\Pages\CreateDocumentImport;
use App\Filament\Resources\DocumentImports\Pages\ListDocumentImports;
use App\Filament\Resources\DocumentImports\Pages\ReviewPreview;
use App\Filament\Resources\DocumentImports\Pages\ViewDocumentImport;
use App\Filament\Resources\DocumentImports\Schemas\DocumentImportForm;
use App\Filament\Resources\DocumentImports\Tables\DocumentImportsTable;
use App\Models\ImportHistory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DocumentImportResource extends Resource
{
    protected static ?string $model = ImportHistory::class;

    protected static UnitEnum|string|null $navigationGroup = 'Documents';

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.documents');
    }

    public static function getModelLabel(): string
    {
        return __('Document Import');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Document Imports');
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentArrowUp;

    protected static ?int $navigationSort = 100;

    public static function form(Schema $schema): Schema
    {
        return DocumentImportForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocumentImportsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocumentImports::route('/'),
            'create' => CreateDocumentImport::route('/create'),
            'view' => ViewDocumentImport::route('/{record}'),
            'configure-mapping' => ConfigureMapping::route('/{record}/configure-mapping'),
            'review-preview' => ReviewPreview::route('/{record}/review-preview'),
        ];
    }
}
