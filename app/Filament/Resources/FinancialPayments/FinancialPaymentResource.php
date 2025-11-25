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

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    

    protected static ?int $navigationSort = 2;
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
