<?php

namespace App\Filament\Resources\PurchaseInvoices;

use App\Filament\Resources\PurchaseInvoices\Schemas\PurchaseInvoiceForm;
use App\Filament\Resources\PurchaseInvoices\Tables\PurchaseInvoicesTable;
use App\Models\PurchaseInvoice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class PurchaseInvoiceResource extends Resource
{
    protected static ?string $model = PurchaseInvoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;
    protected static string|UnitEnum|null $navigationGroup = 'Suppliers';

    protected static ?string $navigationLabel = 'Purchase Invoices';

    protected static ?int $navigationSort = 5;

    public static function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return PurchaseInvoiceForm::configure($schema);
    }

    public static function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return PurchaseInvoicesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\PurchaseInvoices\Pages\ListPurchaseInvoices::route('/'),
            'create' => \App\Filament\Resources\PurchaseInvoices\Pages\CreatePurchaseInvoice::route('/create'),
            'edit' => \App\Filament\Resources\PurchaseInvoices\Pages\EditPurchaseInvoice::route('/{record}/edit'),
        ];
    }
}
