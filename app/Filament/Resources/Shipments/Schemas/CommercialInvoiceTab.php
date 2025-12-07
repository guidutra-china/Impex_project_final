<?php

namespace App\Filament\Resources\Shipments\Schemas;

use App\Models\CommercialInvoice;
use App\Models\CompanySetting;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Forms\Set;

class CommercialInvoiceTab
{
    public static function make(): Tabs\Tab
    {
        return Tabs\Tab::make('Commercial Invoice')
            ->schema([
                // Row 1: Order Info + Configuration (2 columns)
                Grid::make(2)
                    ->schema([
                        Section::make('Order')
                            ->description('Order reference information')
                            ->schema([
                                Placeholder::make('order_number')
                                    ->label('Order Number')
                                    ->content(fn ($record) => $record?->order?->order_number ?? 'N/A'),
                                
                                Placeholder::make('proforma_invoices')
                                    ->label('Proforma Invoices')
                                    ->content(fn ($record) => $record?->proformaInvoices->pluck('proforma_number')->join(', ') ?? 'N/A'),
                            ])
                            ->collapsible()
                            ->collapsed(false),
                        
                        Section::make('Configuration')
                            ->description('Customs discount settings')
                            ->schema([
                                TextInput::make('commercialInvoice.customs_discount_percentage')
                                    ->label('Customs Discount (%)')
                                    ->numeric()
                                    ->suffix('%')
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->default(0)
                                    ->helperText('Percentage to reduce values in Customs version'),
                                
                                Placeholder::make('customs_info')
                                    ->label('')
                                    ->content('The customs discount will only affect the "Customs" version. The "Original" version will always show real values.')
                                    ->columnSpanFull(),
                            ])
                            ->columns(1)
                            ->collapsible()
                            ->collapsed(false),
                    ]),
                
                // Row 2: Additional Information (full width, 2 columns inside)
                Section::make('Additional Information')
                    ->schema([
                        Textarea::make('commercialInvoice.notes')
                            ->label('Notes')
                            ->rows(3)
                            ->placeholder('Any additional notes or remarks'),
                        
                        Textarea::make('commercialInvoice.terms_and_conditions')
                            ->label('Terms and Conditions')
                            ->rows(3)
                            ->placeholder('Terms and conditions for this invoice'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(false),
                
                // Row 3: Display Options (full width, single column)
                Section::make('Display Options')
                    ->description('Control what information appears in the PDF/Excel')
                    ->schema([
                        Toggle::make('commercialInvoice.display_options.show_payment_terms')
                            ->label('Show Payment Terms')
                            ->default(true)
                            ->inline(false),
                        
                        Toggle::make('commercialInvoice.display_options.show_bank_info')
                            ->label('Show Bank Information')
                            ->default(true)
                            ->inline(false),
                        
                        Toggle::make('commercialInvoice.display_options.show_exporter_details')
                            ->label('Show Exporter Details')
                            ->default(true)
                            ->dehydrated()
                            ->inline(false),
                        
                        Toggle::make('commercialInvoice.display_options.show_importer_details')
                            ->label('Show Importer Details')
                            ->default(true)
                            ->dehydrated()
                            ->inline(false),
                        
                        Toggle::make('commercialInvoice.display_options.show_shipping_details')
                            ->label('Show Shipping Details')
                            ->default(true)
                            ->inline(false),
                        
                        Toggle::make('commercialInvoice.display_options.show_supplier_code')
                            ->label('Show Supplier Product Code')
                            ->default(false)
                            ->inline(false),
                        
                        Toggle::make('commercialInvoice.display_options.show_hs_codes')
                            ->label('Show HS Codes')
                            ->default(true)
                            ->inline(false),
                        
                        Toggle::make('commercialInvoice.display_options.show_country_of_origin')
                            ->label('Show Country of Origin')
                            ->default(true)
                            ->inline(false),
                        
                        Toggle::make('commercialInvoice.display_options.show_weight_volume')
                            ->label('Show Weight & Volume')
                            ->default(false)
                            ->inline(false),
                    ])
                    ->columns(1)
                    ->collapsible()
                    ->collapsed(false),
                
                // Row 4: Exporter Details (collapsed by default)
                Section::make('Exporter Details')
                    ->description('Information about the exporter (your company) - Leave empty to use Company Settings')
                    ->schema([
                        TextInput::make('commercialInvoice.exporter_name')
                            ->label('Company Name')
                            ->maxLength(255)
                            ->placeholder(fn () => CompanySetting::current()?->company_name ?? 'From Company Settings'),
                        
                        Textarea::make('commercialInvoice.exporter_address')
                            ->label('Address')
                            ->rows(3)
                            ->placeholder(fn () => CompanySetting::current()?->full_address ?? 'From Company Settings'),
                        
                        TextInput::make('commercialInvoice.exporter_tax_id')
                            ->label('Tax ID / VAT Number')
                            ->maxLength(100)
                            ->placeholder(fn () => CompanySetting::current()?->tax_id ?? 'From Company Settings'),
                        
                        TextInput::make('commercialInvoice.exporter_country')
                            ->label('Country')
                            ->maxLength(100)
                            ->placeholder(fn () => CompanySetting::current()?->country ?? 'From Company Settings'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(true),
                
                // Row 5: Importer Details (collapsed by default)
                Section::make('Importer Details')
                    ->description('Information about the importer (customer) - Leave empty to use Customer data')
                    ->schema([
                        TextInput::make('commercialInvoice.importer_name')
                            ->label('Company Name')
                            ->maxLength(255)
                            ->placeholder('From Customer'),
                        
                        Textarea::make('commercialInvoice.importer_address')
                            ->label('Address')
                            ->rows(3)
                            ->placeholder('From Customer'),
                        
                        TextInput::make('commercialInvoice.importer_tax_id')
                            ->label('Tax ID / VAT Number')
                            ->maxLength(100)
                            ->placeholder('From Customer'),
                        
                        TextInput::make('commercialInvoice.importer_country')
                            ->label('Country')
                            ->maxLength(100)
                            ->placeholder('From Customer'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(true),
                
                // Row 6: Bank Information (collapsed by default)
                Section::make('Bank Information')
                    ->description('Bank details for payment - Leave empty to use Company Settings')
                    ->schema([
                        TextInput::make('commercialInvoice.bank_name')
                            ->label('Bank Name')
                            ->maxLength(255)
                            ->placeholder(fn () => CompanySetting::current()?->bank_name ?? 'From Company Settings'),
                        
                        TextInput::make('commercialInvoice.bank_account')
                            ->label('Account Number')
                            ->maxLength(100)
                            ->placeholder(fn () => CompanySetting::current()?->bank_account_number ?? 'From Company Settings'),
                        
                        TextInput::make('commercialInvoice.bank_swift')
                            ->label('SWIFT Code')
                            ->maxLength(20)
                            ->placeholder(fn () => CompanySetting::current()?->bank_swift_code ?? 'From Company Settings'),
                        
                        Textarea::make('commercialInvoice.bank_address')
                            ->label('Bank Address')
                            ->rows(2)
                            ->placeholder('Bank address (optional)'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(true),
            ])
            ->visible(fn ($record) => $record && in_array($record->status, ['on_board', 'in_transit', 'customs_clearance', 'delivered']));
    }
}
