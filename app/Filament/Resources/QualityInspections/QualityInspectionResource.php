<?php

namespace App\Filament\Resources\QualityInspections;

use App\Filament\Resources\QualityInspections\Pages\CreateQualityInspection;
use App\Filament\Resources\QualityInspections\Pages\EditQualityInspection;
use App\Filament\Resources\QualityInspections\Pages\ListQualityInspections;
use App\Filament\Resources\QualityInspections\Schemas\QualityInspectionForm;
use App\Filament\Resources\QualityInspections\Tables\QualityInspectionsTable;
use App\Models\QualityInspection;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class QualityInspectionResource extends Resource
{
    protected static ?string $model = QualityInspection::class;

    protected static ?string $navigationGroup = 'Purchasing & Inventory';

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return QualityInspectionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QualityInspectionsTable::configure($table);
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
            'index' => ListQualityInspections::route('/'),
            'create' => CreateQualityInspection::route('/create'),
            'edit' => EditQualityInspection::route('/{record}/edit'),
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
