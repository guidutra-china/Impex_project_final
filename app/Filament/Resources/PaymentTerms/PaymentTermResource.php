<?php

namespace App\Filament\Resources\PaymentTerms;

use App\Filament\Resources\PaymentTerms\RelationManagers\StagesRelationManager;
use App\Filament\Resources\PaymentTerms\Pages\CreatePaymentTerm;
use App\Filament\Resources\PaymentTerms\Pages\EditPaymentTerm;
use App\Filament\Resources\PaymentTerms\Pages\ListPaymentTerms;
use App\Filament\Resources\PaymentTerms\Schemas\PaymentTermForm;
use App\Filament\Resources\PaymentTerms\Tables\PaymentTermsTable;
use App\Models\PaymentTerm;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PaymentTermResource extends Resource
{
    protected static ?string $model = PaymentTerm::class;

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?int $navigationSort = 7;


    protected static ?int $navigationSort = 15;
    protected static string | UnitEnum | null $navigationGroup = 'Setup';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    public static function form(Schema $schema): Schema
    {
        return PaymentTermForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentTermsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            StagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPaymentTerms::route('/'),
            'create' => CreatePaymentTerm::route('/create'),
            'edit' => EditPaymentTerm::route('/{record}/edit'),
        ];
    }
}
