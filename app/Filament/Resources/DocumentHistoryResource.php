<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentHistory\Tables\DocumentHistoryTable;
use App\Models\GeneratedDocument;
use Filament\Resources\Resource;
use Filament\Tables\Table;

class DocumentHistoryResource extends Resource
{
    protected static ?string $model = GeneratedDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationLabel = 'Document History';

    protected static ?string $modelLabel = 'Document History';

    protected static ?string $pluralModelLabel = 'Document History';

    protected static ?string $navigationGroup = 'Documents';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return DocumentHistoryTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\DocumentHistoryResource\Pages\ListDocumentHistory::route('/'),
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
