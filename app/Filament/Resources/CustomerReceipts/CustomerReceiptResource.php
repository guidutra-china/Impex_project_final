<?php

namespace App\Filament\Resources\CustomerReceipts;

use App\Filament\Resources\CustomerReceipts\Pages\CreateCustomerReceipt;
use App\Filament\Resources\CustomerReceipts\Pages\EditCustomerReceipt;
use App\Filament\Resources\CustomerReceipts\Pages\ListCustomerReceipts;
use App\Filament\Resources\CustomerReceipts\Schemas\CustomerReceiptForm;
use App\Filament\Resources\CustomerReceipts\Tables\CustomerReceiptsTable;
use App\Models\CustomerReceipt;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class CustomerReceiptResource extends Resource
{
    protected static ?string $model = CustomerReceipt::class;
    protected static ?int $navigationSort = 7;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCurrencyDollar;
    protected static string|UnitEnum|null $navigationGroup = 'Finance';

    public static function form(Schema $schema): Schema
    {
        return CustomerReceiptForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerReceiptsTable::configure($table);
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
            'index' => ListCustomerReceipts::route('/'),
            'create' => CreateCustomerReceipt::route('/create'),
            'edit' => EditCustomerReceipt::route('/{record}/edit'),
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
