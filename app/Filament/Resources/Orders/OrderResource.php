<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\RelationManagers\SupplierQuotesRelationManager;
use App\Filament\Resources\Orders\RelationManagers\SuppliersToQuoteRelationManager;
use App\Filament\Resources\Orders\Schemas\OrderForm;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Filament\Resources\Orders\RelationManagers\ItemsRelationManager;
use App\Models\Order;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use BackedEnum;
use UnitEnum;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static UnitEnum|string|null $navigationGroup = 'Sales & Orders';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'RFQs';
    protected static ?string $modelLabel = 'RFQ';
    protected static ?string $pluralModelLabel = 'RFQs';

    protected static ?string $recordTitleAttribute = 'order_number';

    public static function getGlobalSearchResultTitle($record): string
    {
        return $record->order_number . ' - ' . ($record->customer?->name ?? 'No Customer');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['order_number', 'customer_nr_rfq', 'customer.name', 'customer.email'];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Customer' => $record->customer?->name ?? 'N/A',
            'Status' => ucfirst($record->status),
            'Customer Ref' => $record->customer_nr_rfq ?? 'N/A',
        ];
    }

    public static function getGlobalSearchResultActions($record): array
    {
        return [
            \Filament\Actions\Action::make('edit')
                ->url(static::getUrl('edit', ['record' => $record]))
                ->icon('heroicon-o-pencil'),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return OrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            SuppliersToQuoteRelationManager::class,
            ItemsRelationManager::class,
            SupplierQuotesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }
}
