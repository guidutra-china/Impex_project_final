<?php

namespace App\Filament\Resources\CommercialInvoices;

use App\Filament\Resources\CommercialInvoices\Pages\CreateCommercialInvoice;
use App\Filament\Resources\CommercialInvoices\Pages\EditCommercialInvoice;
use App\Filament\Resources\CommercialInvoices\Pages\ListCommercialInvoices;
use App\Filament\Resources\CommercialInvoices\Pages\ViewCommercialInvoice;
use App\Filament\Resources\CommercialInvoices\Schemas\CommercialInvoiceForm;
use App\Filament\Resources\CommercialInvoices\Tables\CommercialInvoicesTable;
use App\Models\CommercialInvoice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class CommercialInvoiceResource extends Resource
{
    protected static ?string $model = CommercialInvoice::class;

    protected static UnitEnum|string|null $navigationGroup = 'Logistics & Shipping';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 15;

    protected static ?string $navigationLabel = 'Commercial Invoices';

    protected static ?string $modelLabel = 'Commercial Invoice';

    protected static ?string $pluralModelLabel = 'Commercial Invoices';

    public static function form(Schema $schema): Schema
    {
        return CommercialInvoiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CommercialInvoicesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            // Items will be shown in a tab within the form
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCommercialInvoices::route('/'),
            'create' => CreateCommercialInvoice::route('/create'),
            'view' => ViewCommercialInvoice::route('/{record}'),
            'edit' => EditCommercialInvoice::route('/{record}/edit'),
        ];
    }
}
