<?php

namespace App\Filament\Resources\SupplierQuotes\ExchangeRates;

use App\Filament\Resources\SupplierQuotes\ExchangeRates\Pages\CreateExchangeRate;
use App\Filament\Resources\SupplierQuotes\ExchangeRates\Pages\EditExchangeRate;
use App\Filament\Resources\SupplierQuotes\ExchangeRates\Pages\ListExchangeRates;
use App\Filament\Resources\SupplierQuotes\ExchangeRates\Schemas\ExchangeRateForm;
use App\Filament\Resources\SupplierQuotes\ExchangeRates\Tables\ExchangeRatesTable;
use App\Models\ExchangeRate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ExchangeRateResource extends Resource
{
    protected static ?string $model = ExchangeRate::class;

    protected static ?int $navigationSort = 1
    ;
    protected static string | UnitEnum | null $navigationGroup = 'Quotations';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;



    public static function form(Schema $schema): Schema
    {
        return ExchangeRateForm::configure($schema);
    }


    public static function table(Table $table): Table
    {
        return ExchangeRatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExchangeRates::route('/'),
            'create' => CreateExchangeRate::route('/create'),
            'edit' => EditExchangeRate::route('/{record}/edit'),
        ];
    }
}
