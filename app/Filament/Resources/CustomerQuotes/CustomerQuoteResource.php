<?php

namespace App\Filament\Resources\CustomerQuotes;

use App\Filament\Resources\CustomerQuotes\Pages\CreateCustomerQuote;
use App\Filament\Resources\CustomerQuotes\Pages\EditCustomerQuote;
use App\Filament\Resources\CustomerQuotes\Pages\ListCustomerQuotes;
use App\Filament\Resources\CustomerQuotes\Pages\ViewCustomerQuote;
use App\Filament\Resources\CustomerQuotes\Schemas\CustomerQuoteForm;
use App\Filament\Resources\CustomerQuotes\Tables\CustomerQuotesTable;
use App\Filament\Resources\CustomerQuotes\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\CustomerQuotes\RelationManagers\ProductSelectionsRelationManager;
use App\Models\CustomerQuote;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use BackedEnum;
use UnitEnum;

class CustomerQuoteResource extends Resource
{
    protected static ?string $model = CustomerQuote::class;

    protected static UnitEnum|string|null $navigationGroup = 'Sales & Quotations';

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.sales_quotations');
    }

    public static function getModelLabel(): string
    {
        return 'Customer Quote';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Customer Quotes';
    }

    public static function getNavigationLabel(): string
    {
        return 'Customer Quotes';
    }

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 15;

    protected static ?string $recordTitleAttribute = 'quote_number';

    public static function getGlobalSearchResultTitle($record): string
    {
        return $record->quote_number . ' - ' . ($record->order?->customer?->name ?? 'No Customer');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['quote_number', 'order.order_number', 'order.customer.name'];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'RFQ' => $record->order?->order_number ?? 'N/A',
            'Customer' => $record->order?->customer?->name ?? 'N/A',
            'Status' => ucfirst($record->status),
        ];
    }

    public static function getGlobalSearchResultActions($record): array
    {
        return [
            \Filament\Actions\Action::make('view')
                ->url(static::getUrl('view', ['record' => $record]))
                ->icon('heroicon-o-eye'),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return CustomerQuoteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerQuotesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
            ProductSelectionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomerQuotes::route('/'),
            'create' => CreateCustomerQuote::route('/create'),
            'view' => ViewCustomerQuote::route('/{record}'),
            'edit' => EditCustomerQuote::route('/{record}/edit'),
        ];
    }
}
