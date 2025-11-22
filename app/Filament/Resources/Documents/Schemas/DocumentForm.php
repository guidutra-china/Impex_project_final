<?php

namespace App\Filament\Resources\Documents\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('document_number')
                    ->required(),
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                Select::make('document_type')
                    ->options([
            'commercial_invoice' => 'Commercial invoice',
            'proforma_invoice' => 'Proforma invoice',
            'packing_list' => 'Packing list',
            'bill_of_lading' => 'Bill of lading',
            'certificate_of_origin' => 'Certificate of origin',
            'quality_certificate' => 'Quality certificate',
            'insurance_certificate' => 'Insurance certificate',
            'customs_declaration' => 'Customs declaration',
            'contract' => 'Contract',
            'purchase_order' => 'Purchase order',
            'sales_order' => 'Sales order',
            'other' => 'Other',
        ])
                    ->required(),
                TextInput::make('related_type'),
                TextInput::make('related_id')
                    ->numeric(),
                TextInput::make('file_path')
                    ->required(),
                TextInput::make('file_name')
                    ->required(),
                TextInput::make('safe_filename')
                    ->required(),
                TextInput::make('mime_type')
                    ->required(),
                TextInput::make('file_size')
                    ->required()
                    ->numeric(),
                Select::make('status')
                    ->options(['draft' => 'Draft', 'valid' => 'Valid', 'expired' => 'Expired', 'cancelled' => 'Cancelled'])
                    ->default('valid')
                    ->required(),
                DatePicker::make('issue_date'),
                DatePicker::make('expiry_date'),
                Toggle::make('is_public')
                    ->required(),
                Toggle::make('is_confidential')
                    ->required(),
                Textarea::make('notes')
                    ->columnSpanFull(),
                TextInput::make('uploaded_by')
                    ->numeric(),
            ]);
    }
}
