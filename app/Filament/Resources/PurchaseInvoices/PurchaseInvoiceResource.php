<?php

namespace App\Filament\Resources\PurchaseInvoices;

use App\Filament\Resources\PurchaseInvoices\Schemas\PurchaseInvoiceForm;
use App\Filament\Resources\PurchaseInvoices\Tables\PurchaseInvoicesTable;
use App\Models\PurchaseInvoice;
use Filament\Resources\Resource;

class PurchaseInvoiceResource extends Resource
{
    protected static ?string $model = PurchaseInvoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

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
