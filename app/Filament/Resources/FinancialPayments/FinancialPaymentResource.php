<?php
namespace App\Filament\Resources\FinancialPayments;
use App\Filament\Resources\FinancialPayments\Pages\CreateFinancialPayment;
use App\Filament\Resources\FinancialPayments\Pages\EditFinancialPayment;
use App\Filament\Resources\FinancialPayments\Pages\ListFinancialPayments;
use App\Filament\Resources\FinancialPayments\RelationManagers\AllocationsRelationManager;
use App\Filament\Resources\FinancialPayments\Schemas\FinancialPaymentForm;
use App\Filament\Resources\FinancialPayments\Tables\FinancialPaymentsTable;
use App\Models\FinancialPayment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;
class FinancialPaymentResource extends Resource
{
    protected static ?string $model = FinancialPayment::class;

    protected static UnitEnum|string|null $navigationGroup = 'Finance';

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.finance');
    }

    public static function getModelLabel(): string
    {
        return __('navigation.financial_payments');
    }

    public static function getPluralModelLabel(): string
    {
        return __('navigation.financial_payments');
    }

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-credit-card';

    

    protected static ?int $navigationSort = 20;
    public static function form(Schema $schema): Schema
    {
        return FinancialPaymentForm::configure($schema);
    }
    public static function table(Table $table): Table
    {
        return FinancialPaymentsTable::configure($table);
    }
    public static function getRelations(): array
    {
        return [
            AllocationsRelationManager::class,
        ];
    }
    public static function getPages(): array
    {
        return [
            'index' => ListFinancialPayments::route('/'),
            'create' => CreateFinancialPayment::route('/create'),
            'edit' => EditFinancialPayment::route('/{record}/edit'),
        ];
    }
}
