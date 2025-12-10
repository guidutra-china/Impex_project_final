<?php

namespace App\Filament\Resources\DocumentImports\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class DocumentImportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('import_type')
                    ->label('Import Type')
                    ->options([
                        'products' => 'Products',
                        'suppliers' => 'Suppliers (Coming Soon)',
                        'clients' => 'Clients (Coming Soon)',
                        'quotes' => 'Supplier Quotes (Coming Soon)',
                    ])
                    ->default('products')
                    ->required()
                    ->helperText('Select what type of data you want to import'),

                FileUpload::make('file')
                    ->label('File')
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel',
                        'application/pdf',
                    ])
                    ->maxSize(20480) // 20MB
                    ->required()
                    ->helperText('Upload Excel (.xlsx, .xls) or PDF file. Max size: 20MB. AI will analyze automatically after creation.'),
            ]);
    }
}
