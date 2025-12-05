<?php

namespace App\Filament\Resources\CommercialInvoices;

use App\Filament\Resources\CommercialInvoices\Schemas\CommercialInvoiceForm;
use App\Filament\Resources\CommercialInvoices\Tables\CommercialInvoicesTable;
use App\Models\CommercialInvoice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class CommercialInvoiceResource extends Resource
{
    protected static ?string $model = CommercialInvoice::class;

    protected static UnitEnum|string|null $navigationGroup = 'Logistics & Shipping';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 50;

    protected static ?string $navigationLabel = 'Commercial Invoices';

    

    public static function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return CommercialInvoiceForm::configure($schema);
    }

    public static function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return CommercialInvoicesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\CommercialInvoices\Pages\ListCommercialInvoices::route('/'),
            'create' => \App\Filament\Resources\CommercialInvoices\Pages\CreateCommercialInvoice::route('/create'),
            'edit' => \App\Filament\Resources\CommercialInvoices\Pages\EditCommercialInvoice::route('/{record}/edit'),
        ];
    }
}
