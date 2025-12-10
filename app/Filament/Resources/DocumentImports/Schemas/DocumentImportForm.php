<?php

namespace App\Filament\Resources\DocumentImports\Schemas;

use App\Filament\Traits\SecureFileUpload;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class DocumentImportForm
{
    use SecureFileUpload;

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
                    ->disk('private')
                    ->directory('imports')
                    ->visibility('private')
                    ->acceptedFileTypes(self::getAcceptedFileTypes('spreadsheets'))
                    ->maxSize(self::getMaxFileSize('spreadsheets'))
                    ->required()
                    ->helperText('Upload Excel (.xlsx, .xls) or PDF file. Max size: 20MB. AI will analyze automatically after creation.')
                    ->saveUploadedFileUsing(self::secureUploadPrivate('spreadsheets', 'imports')),
            ]);
    }
}
