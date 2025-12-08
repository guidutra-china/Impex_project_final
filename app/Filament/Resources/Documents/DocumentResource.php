<?php

namespace App\Filament\Resources\Documents;

use App\Filament\Resources\Documents\Pages\CreateDocument;
use App\Filament\Resources\Documents\Pages\EditDocument;
use App\Filament\Resources\Documents\Pages\ListDocuments;
use App\Filament\Resources\Documents\Schemas\DocumentForm;
use App\Filament\Resources\Documents\Tables\DocumentsTable;
use App\Models\Document;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static UnitEnum|string|null $navigationGroup = 'Contacts';

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.contacts');
    }

    public static function getModelLabel(): string
    {
        return __('navigation.suppliers_documents');
    }

    public static function getPluralModelLabel(): string
    {
        return __('navigation.suppliers_documents');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.suppliers_documents');
    }

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document';

    protected static ?int $navigationSort = 40;

    protected static ?string $navigationLabel = 'Suppliers Documents';

    protected static ?string $modelLabel = 'Supplier Document';

    protected static ?string $pluralModelLabel = 'Suppliers Documents';

    public static function form(Schema $schema): Schema
    {
        return DocumentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocumentsTable::configure($table);
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
            'index' => ListDocuments::route('/'),
            'create' => CreateDocument::route('/create'),
            'edit' => EditDocument::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
