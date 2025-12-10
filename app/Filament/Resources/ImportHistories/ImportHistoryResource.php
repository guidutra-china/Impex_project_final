<?php

namespace App\Filament\Resources\ImportHistories;

use App\Filament\Resources\ImportHistories\Pages\CreateImportHistory;
use App\Filament\Resources\ImportHistories\Pages\EditImportHistory;
use App\Filament\Resources\ImportHistories\Pages\ListImportHistories;
use App\Filament\Resources\ImportHistories\Schemas\ImportHistoryForm;
use App\Filament\Resources\ImportHistories\Tables\ImportHistoriesTable;
use App\Models\ImportHistory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ImportHistoryResource extends Resource
{
    protected static ?string $model = ImportHistory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ImportHistoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ImportHistoriesTable::configure($table);
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
            'index' => ListImportHistories::route('/'),
            'create' => CreateImportHistory::route('/create'),
            'edit' => EditImportHistory::route('/{record}/edit'),
        ];
    }
}
