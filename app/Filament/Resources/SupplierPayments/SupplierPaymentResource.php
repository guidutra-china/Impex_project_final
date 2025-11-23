<?php

namespace App\Filament\Resources\SupplierPayments;

use App\Filament\Resources\SupplierPayments\Pages\CreateSupplierPayment;
use App\Filament\Resources\SupplierPayments\Pages\EditSupplierPayment;
use App\Filament\Resources\SupplierPayments\Pages\ListSupplierPayments;
use App\Filament\Resources\SupplierPayments\Schemas\SupplierPaymentForm;
use App\Filament\Resources\SupplierPayments\Tables\SupplierPaymentsTable;
use App\Models\SupplierPayment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class SupplierPaymentResource extends Resource
{
    protected static ?string $model = SupplierPayment::class;

    protected static ?int $navigationSort = 8;


    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;
    protected static string|UnitEnum|null $navigationGroup = 'Finance';

    public static function form(Schema $schema): Schema
    {
        return SupplierPaymentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupplierPaymentsTable::configure($table);
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
            'index' => ListSupplierPayments::route('/'),
            'create' => CreateSupplierPayment::route('/create'),
            'edit' => EditSupplierPayment::route('/{record}/edit'),
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
