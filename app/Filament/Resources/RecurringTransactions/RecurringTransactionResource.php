<?php
namespace App\Filament\Resources\RecurringTransactions;
use App\Filament\Resources\RecurringTransactions\Pages\CreateRecurringTransaction;
use App\Filament\Resources\RecurringTransactions\Pages\EditRecurringTransaction;
use App\Filament\Resources\RecurringTransactions\Pages\ListRecurringTransactions;
use App\Filament\Resources\RecurringTransactions\Schemas\RecurringTransactionForm;
use App\Filament\Resources\RecurringTransactions\Tables\RecurringTransactionsTable;
use App\Models\RecurringTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;
class RecurringTransactionResource extends Resource
{
    protected static ?string $model = RecurringTransaction::class;
    protected static ?int $navigationSort = 3;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;
    protected static string|UnitEnum|null $navigationGroup = 'Financeiro';
    public static function form(Schema $schema): Schema
    {
        return RecurringTransactionForm::configure($schema);
    }
    public static function table(Table $table): Table
    {
        return RecurringTransactionsTable::configure($table);
    }
    public static function getRelations(): array
    {
        return [];
    }
    public static function getPages(): array
    {
        return [
            'index' => ListRecurringTransactions::route('/'),
            'create' => CreateRecurringTransaction::route('/create'),
            'edit' => EditRecurringTransaction::route('/{record}/edit'),
        ];
    }
}
