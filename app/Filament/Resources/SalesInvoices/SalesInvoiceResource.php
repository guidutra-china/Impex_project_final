<?php

namespace App\Filament\Resources\SalesInvoices;

use App\Filament\Resources\SalesInvoices\Schemas\SalesInvoiceForm;
use App\Filament\Resources\SalesInvoices\Tables\SalesInvoicesTable;
use App\Models\SalesInvoice;
use Filament\Resources\Resource;

class SalesInvoiceResource extends Resource
{
    protected static ?string $model = SalesInvoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static ?string $navigationLabel = 'Sales Invoices';

    protected static ?int $navigationSort = 6;

    public static function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return SalesInvoiceForm::configure($schema);
    }

    public static function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return SalesInvoicesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\SalesInvoices\Pages\ListSalesInvoices::route('/'),
            'create' => \App\Filament\Resources\SalesInvoices\Pages\CreateSalesInvoice::route('/create'),
            'edit' => \App\Filament\Resources\SalesInvoices\Pages\EditSalesInvoice::route('/{record}/edit'),
        ];
    }
}
