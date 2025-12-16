<?php

namespace App\Filament\Resources\CompanySettings\Schemas;

use App\Filament\Traits\SecureFileUpload;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

class CompanySettingsForm
{
    use SecureFileUpload;
    public static function getSchema(): array
    {
        return [
            Section::make('Basic Information')
                ->schema([
                    TextInput::make('company_name')
                        ->label(__('fields.company_name'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),

                    FileUpload::make('logo_path')
                        ->label('Company Logo')
                        ->image()
                        ->disk('public')
                        ->directory('company')
                        ->visibility('public')
                        ->imageEditor()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
                        ->maxSize(5120)
                        ->helperText('Upload your company logo (max 5MB, JPG/PNG/GIF/WEBP only). Recommended size: 300x100px')
                        ->columnSpan(2),

                    Textarea::make('address')
                        ->label(__('fields.address'))
                        ->rows(2)
                        ->columnSpan(2),

                    TextInput::make('city')
                        ->label(__('fields.city'))
                        ->maxLength(255),

                    TextInput::make('state')
                        ->label(__('fields.state'))
                        ->maxLength(255),

                    TextInput::make('zip_code')
                        ->label(__('fields.zip'))
                        ->maxLength(20),

                    TextInput::make('country')
                        ->label(__('fields.country'))
                        ->maxLength(255),
                ])
                ->columns(2),

            Section::make('Contact Information')
                ->schema([
                    TextInput::make('phone')
                        ->label(__('fields.phone'))
                        ->tel()
                        ->maxLength(255),

                    TextInput::make('email')
                        ->label(__('fields.email'))
                        ->email()
                        ->maxLength(255),

                    TextInput::make('website')
                        ->label(__('fields.website'))
                        ->url()
                        ->maxLength(255)
                        ->columnSpan(2),
                ])
                ->columns(2),

            Section::make('Legal Information')
                ->schema([
                    TextInput::make('tax_id')
                        ->label(__('fields.tax_id'))
                        ->maxLength(255),

                    TextInput::make('registration_number')
                        ->label('Company Registration Number')
                        ->maxLength(255),
                ])
                ->columns(2),

            Section::make('Banking Information')
                ->description('This information will be displayed on invoices for payment')
                ->schema([
                    TextInput::make('bank_name')
                        ->label(__('fields.bank_name'))
                        ->maxLength(255),

                    TextInput::make('bank_account_number')
                        ->label(__('fields.account_number'))
                        ->maxLength(255),

                    TextInput::make('bank_routing_number')
                        ->label('Routing Number / Sort Code')
                        ->maxLength(255),

                    TextInput::make('bank_swift_code')
                        ->label(__('fields.swift_code'))
                        ->maxLength(255),
                ])
                ->columns(2)
                ->collapsible(),

            Section::make('Document Settings')
                ->schema([
                    TextInput::make('invoice_prefix')
                        ->label('Invoice Prefix')
                        ->default('INV')
                        ->maxLength(10)
                        ->helperText('Prefix for invoice numbers (e.g., INV, PI, SI)'),

                    TextInput::make('quote_prefix')
                        ->label('Quote Prefix')
                        ->default('QT')
                        ->maxLength(10)
                        ->helperText('Prefix for quote numbers'),

                    TextInput::make('po_prefix')
                        ->label('Purchase Order Prefix')
                        ->default('PO')
                        ->maxLength(10)
                        ->helperText('Prefix for purchase order numbers'),

                    Textarea::make('footer_text')
                        ->label('Invoice Footer Text')
                        ->rows(3)
                        ->columnSpan(2)
                        ->helperText('This text will appear at the bottom of all invoices')
                        ->placeholder('Thank you for your business!'),

                    Textarea::make('rfq_default_instructions')
                        ->label('RFQ Default Quotation Instructions')
                        ->rows(8)
                        ->columnSpan(2)
                        ->helperText('Default instructions shown in all RFQ PDFs (can be overridden per RFQ)')
                        ->placeholder("Please provide your best quotation including:\n\n• Unit price and total price for each item\n• Lead time / delivery time\n• Minimum Order Quantity (MOQ) if applicable\n• Payment terms and conditions\n• Validity period of your quotation\n• Any additional costs (tooling, setup, shipping, etc.)\n\nPlease submit your quotation by the specified deadline."),
                ])
                ->columns(2)
                ->collapsible(),
        ];
    }
}
