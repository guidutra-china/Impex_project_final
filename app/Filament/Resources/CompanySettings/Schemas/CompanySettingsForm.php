<?php

namespace App\Filament\Resources\CompanySettings\Schemas;

use Filament\Schemas\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Textarea;
use Filament\Schemas\Components\TextInput;

class CompanySettingsForm
{
    public static function getSchema(): array
    {
        return [
            Section::make('Basic Information')
                ->schema([
                    TextInput::make('company_name')
                        ->label('Company Name')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),

                    FileUpload::make('logo_path')
                        ->label('Company Logo')
                        ->image()
                        ->disk('public')
                        ->directory('company')
                        ->imageEditor()
                        ->maxSize(2048)
                        ->helperText('Upload your company logo (max 2MB). Recommended size: 300x100px')
                        ->columnSpan(2),

                    Textarea::make('address')
                        ->label('Street Address')
                        ->rows(2)
                        ->columnSpan(2),

                    TextInput::make('city')
                        ->label('City')
                        ->maxLength(255),

                    TextInput::make('state')
                        ->label('State/Province')
                        ->maxLength(255),

                    TextInput::make('zip_code')
                        ->label('ZIP/Postal Code')
                        ->maxLength(20),

                    TextInput::make('country')
                        ->label('Country')
                        ->maxLength(255),
                ])
                ->columns(2),

            Section::make('Contact Information')
                ->schema([
                    TextInput::make('phone')
                        ->label('Phone Number')
                        ->tel()
                        ->maxLength(255),

                    TextInput::make('email')
                        ->label('Email Address')
                        ->email()
                        ->maxLength(255),

                    TextInput::make('website')
                        ->label('Website')
                        ->url()
                        ->maxLength(255)
                        ->columnSpan(2),
                ])
                ->columns(2),

            Section::make('Legal Information')
                ->schema([
                    TextInput::make('tax_id')
                        ->label('Tax ID / VAT Number')
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
                        ->label('Bank Name')
                        ->maxLength(255),

                    TextInput::make('bank_account_number')
                        ->label('Account Number')
                        ->maxLength(255),

                    TextInput::make('bank_routing_number')
                        ->label('Routing Number / Sort Code')
                        ->maxLength(255),

                    TextInput::make('bank_swift_code')
                        ->label('SWIFT/BIC Code')
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
                ])
                ->columns(2)
                ->collapsible(),
        ];
    }
}
