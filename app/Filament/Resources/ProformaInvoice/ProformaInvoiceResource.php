<?php

namespace App\Filament\Resources\ProformaInvoice;

use App\Filament\Resources\ProformaInvoice\Schemas\ProformaInvoiceForm;
use App\Filament\Resources\ProformaInvoice\Tables\ProformaInvoiceTable;
use App\Models\ProformaInvoice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class ProformaInvoiceResource extends Resource
{
    protected static ?string $model = ProformaInvoice::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Proforma Invoices';

    protected static ?string $modelLabel = 'Proforma Invoice';

    protected static ?string $pluralModelLabel = 'Proforma Invoices';

    protected static UnitEnum|string|null $navigationGroup = 'Sales & Quotations';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return ProformaInvoiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProformaInvoiceTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
            RelationManagers\ShipmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProformaInvoices::route('/'),
            'create' => Pages\CreateProformaInvoice::route('/create'),
            'edit' => Pages\EditProformaInvoice::route('/{record}/edit'),
            'view' => Pages\ViewProformaInvoice::route('/{record}'),
        ];
    }
}
