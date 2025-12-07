<?php

namespace App\Filament\Resources\Shipments\Schemas;

use App\Models\CommercialInvoice;
use App\Models\CompanySetting;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class CommercialInvoiceTab
{
    public static function make(): Tabs\Tab
    {
        return Tabs\Tab::make('Commercial Invoice')
            ->schema([
                Section::make('Configuration')
                    ->description('Configure customs discount and display options for PDF/Excel generation')
                    ->schema([
                        TextInput::make('commercialInvoice.customs_discount_percentage')
                            ->label('Customs Discount (%)')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(0)
                            ->helperText('Percentage to reduce values in Customs version of PDF/Excel'),
                        
                        Placeholder::make('customs_info')
                            ->label('')
                            ->content('The customs discount will only affect the "Customs" version of PDF/Excel. The "Original" version will always show real values.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),
                
                Section::make('Exporter Details')
                    ->description('Information about the exporter (your company)')
                    ->schema([
                        TextInput::make('commercialInvoice.exporter_name')
                            ->label('Company Name')
                            ->maxLength(255)
                            ->default(fn () => CompanySetting::current()?->company_name),
                        
                        Textarea::make('commercialInvoice.exporter_address')
                            ->label('Address')
                            ->rows(3)
                            ->default(fn () => CompanySetting::current()?->full_address),
                        
                        TextInput::make('commercialInvoice.exporter_tax_id')
                            ->label('Tax ID / VAT Number')
                            ->maxLength(100)
                            ->default(fn () => CompanySetting::current()?->tax_id),
                        
                        TextInput::make('commercialInvoice.exporter_country')
                            ->label('Country')
                            ->maxLength(100)
                            ->placeholder('Hong Kong, China, Brazil, etc.')
                            ->default(fn () => CompanySetting::current()?->country),
                    ])
                    ->columns(2)
                    ->collapsible(),
                
                Section::make('Importer Details')
                    ->description('Information about the importer (customer)')
                    ->schema([
                        TextInput::make('commercialInvoice.importer_name')
                            ->label('Company Name')
                            ->maxLength(255),
                        
                        Textarea::make('commercialInvoice.importer_address')
                            ->label('Address')
                            ->rows(3),
                        
                        TextInput::make('commercialInvoice.importer_tax_id')
                            ->label('Tax ID / VAT Number')
                            ->maxLength(100),
                        
                        TextInput::make('commercialInvoice.importer_country')
                            ->label('Country')
                            ->maxLength(100)
                            ->placeholder('Portugal, United States, etc.'),
                    ])
                    ->columns(2)
                    ->collapsible(),
                
                Section::make('Bank Information')
                    ->description('Bank details for payment')
                    ->schema([
                        TextInput::make('commercialInvoice.bank_name')
                            ->label('Bank Name')
                            ->maxLength(255)
                            ->default(fn () => CompanySetting::current()?->bank_name),
                        
                        TextInput::make('commercialInvoice.bank_account')
                            ->label('Account Number')
                            ->maxLength(100)
                            ->default(fn () => CompanySetting::current()?->bank_account_number),
                        
                        TextInput::make('commercialInvoice.bank_swift')
                            ->label('SWIFT Code')
                            ->maxLength(20)
                            ->default(fn () => CompanySetting::current()?->bank_swift_code),
                        
                        Textarea::make('commercialInvoice.bank_address')
                            ->label('Bank Address')
                            ->rows(2)
                            ->default(fn () => CompanySetting::current()?->bank_address),
                    ])
                    ->columns(2)
                    ->collapsible(),
                
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
                    ->collapsible(),
                
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
                    ->columns(4)
                    ->collapsible(),
            ])
            ->visible(fn ($record) => $record && in_array($record->status, ['on_board', 'in_transit', 'customs_clearance', 'delivered']));
    }
}
