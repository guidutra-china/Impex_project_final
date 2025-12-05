<?php

namespace App\Filament\Resources\CommercialInvoices\Schemas;

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
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class CommercialInvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Invoice Tabs')
                    ->tabs([
                        // TAB 1: Basic Information
                        Tabs\Tab::make('Basic Information')
                            ->icon(Heroicon::OutlinedDocumentText)
                            ->schema([
                                Section::make('Invoice Details')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make('invoice_number')
                                                    ->label('Invoice Number')
                                                    ->disabled()
                                                    ->dehydrated(false)
                                                    ->placeholder('Auto-generated'),

                                                DatePicker::make('invoice_date')
                                                    ->label('Invoice Date')
                                                    ->default(now())
                                                    ->required(),

                                                Select::make('shipment_id')
                                                    ->label('Shipment')
                                                    ->relationship('shipment', 'shipment_number')
                                                    ->searchable()
                                                    ->preload()
                                                    ->required()
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, callable $set) {
                                                        if ($state) {
                                                            $shipment = \App\Models\Shipment::find($state);
                                                            if ($shipment && $shipment->customer) {
                                                                // Auto-fill from shipment/customer
                                                                $customer = $shipment->customer;
                                                                $set('importer_name', $customer->name);
                                                                $set('importer_address', $customer->address);
                                                                $set('importer_country', $customer->country);
                                                                $set('importer_phone', $customer->phone);
                                                                $set('importer_email', $customer->email);
                                                            }
                                                        }
                                                    }),
                                            ]),

                                        Grid::make(2)
                                            ->schema([
                                                Select::make('status')
                                                    ->label('Status')
                                                    ->options([
                                                        'draft' => 'Draft',
                                                        'issued' => 'Issued',
                                                        'submitted' => 'Submitted to Customs',
                                                        'cleared' => 'Cleared',
                                                        'cancelled' => 'Cancelled',
                                                    ])
                                                    ->default('draft')
                                                    ->required(),

                                                Select::make('currency_id')
                                                    ->label('Currency')
                                                    ->relationship('currency', 'code')
                                                    ->searchable()
                                                    ->preload()
                                                    ->default(1) // USD
                                                    ->required(),
                                            ]),
                                    ]),
                            ]),

                        // TAB 2: Parties
                        Tabs\Tab::make('Parties')
                            ->icon(Heroicon::OutlinedUsers)
                            ->schema([
                                Section::make('Exporter (Seller)')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('exporter_name')
                                                    ->label('Company Name')
                                                    ->required()
                                                    ->columnSpanFull(),

                                                Textarea::make('exporter_address')
                                                    ->label('Address')
                                                    ->rows(3)
                                                    ->required()
                                                    ->columnSpanFull(),

                                                TextInput::make('exporter_tax_id')
                                                    ->label('Tax ID / VAT / EIN'),

                                                Select::make('exporter_country')
                                                    ->label('Country')
                                                    ->searchable()
                                                    ->options(fn () => \App\Models\Country::pluck('name', 'code'))
                                                    ->required(),

                                                TextInput::make('exporter_phone')
                                                    ->label('Phone')
                                                    ->tel(),

                                                TextInput::make('exporter_email')
                                                    ->label('Email')
                                                    ->email(),
                                            ]),
                                    ]),

                                Section::make('Importer (Buyer)')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('importer_name')
                                                    ->label('Company Name')
                                                    ->required()
                                                    ->columnSpanFull(),

                                                Textarea::make('importer_address')
                                                    ->label('Address')
                                                    ->rows(3)
                                                    ->required()
                                                    ->columnSpanFull(),

                                                TextInput::make('importer_tax_id')
                                                    ->label('Tax ID / VAT'),

                                                Select::make('importer_country')
                                                    ->label('Country')
                                                    ->searchable()
                                                    ->options(fn () => \App\Models\Country::pluck('name', 'code'))
                                                    ->required(),

                                                TextInput::make('importer_phone')
                                                    ->label('Phone')
                                                    ->tel(),

                                                TextInput::make('importer_email')
                                                    ->label('Email')
                                                    ->email(),
                                            ]),
                                    ]),

                                Section::make('Notify Party (Optional)')
                                    ->description('If different from importer')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('notify_party_name')
                                                    ->label('Company Name')
                                                    ->columnSpanFull(),

                                                Textarea::make('notify_party_address')
                                                    ->label('Address')
                                                    ->rows(2)
                                                    ->columnSpanFull(),

                                                TextInput::make('notify_party_phone')
                                                    ->label('Phone')
                                                    ->tel(),
                                            ]),
                                    ])
                                    ->collapsible()
                                    ->collapsed(),
                            ]),

                        // TAB 3: Shipping & Terms
                        Tabs\Tab::make('Shipping & Terms')
                            ->icon(Heroicon::OutlinedTruck)
                            ->schema([
                                Section::make('Shipping Details')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('port_of_loading')
                                                    ->label('Port of Loading')
                                                    ->placeholder('e.g., Shanghai, China'),

                                                TextInput::make('port_of_discharge')
                                                    ->label('Port of Discharge')
                                                    ->placeholder('e.g., Los Angeles, USA'),

                                                Select::make('country_of_origin')
                                                    ->label('Country of Origin')
                                                    ->searchable()
                                                    ->options(fn () => \App\Models\Country::pluck('name', 'code')),

                                                Select::make('country_of_destination')
                                                    ->label('Country of Destination')
                                                    ->searchable()
                                                    ->options(fn () => \App\Models\Country::pluck('name', 'code')),

                                                TextInput::make('vessel_flight_number')
                                                    ->label('Vessel / Flight Number')
                                                    ->columnSpanFull(),
                                            ]),
                                    ]),

                                Section::make('Terms & Conditions')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Select::make('incoterm')
                                                    ->label('Incoterm')
                                                    ->options([
                                                        'EXW' => 'EXW - Ex Works',
                                                        'FCA' => 'FCA - Free Carrier',
                                                        'FOB' => 'FOB - Free On Board',
                                                        'CFR' => 'CFR - Cost and Freight',
                                                        'CIF' => 'CIF - Cost, Insurance and Freight',
                                                        'DAP' => 'DAP - Delivered At Place',
                                                        'DDP' => 'DDP - Delivered Duty Paid',
                                                    ])
                                                    ->searchable(),

                                                TextInput::make('payment_terms')
                                                    ->label('Payment Terms')
                                                    ->placeholder('e.g., 30 days net'),

                                                Select::make('reason_for_export')
                                                    ->label('Reason for Export')
                                                    ->options([
                                                        'sale' => 'Sale',
                                                        'sample' => 'Sample',
                                                        'return' => 'Return',
                                                        'repair' => 'Repair',
                                                        'gift' => 'Gift',
                                                        'other' => 'Other',
                                                    ])
                                                    ->default('sale')
                                                    ->required(),

                                                Textarea::make('terms_of_sale')
                                                    ->label('Terms of Sale')
                                                    ->rows(2)
                                                    ->columnSpanFull(),
                                            ]),
                                    ]),

                                Section::make('Bank Information (Optional)')
                                    ->description('For payment instructions')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('bank_name')
                                                    ->label('Bank Name'),

                                                TextInput::make('bank_swift_code')
                                                    ->label('SWIFT / BIC Code'),

                                                TextInput::make('bank_account_number')
                                                    ->label('Account Number')
                                                    ->columnSpanFull(),

                                                Textarea::make('bank_address')
                                                    ->label('Bank Address')
                                                    ->rows(2)
                                                    ->columnSpanFull(),
                                            ]),
                                    ])
                                    ->collapsible()
                                    ->collapsed(),
                            ]),

                        // TAB 4: Items & Calculations
                        Tabs\Tab::make('Items & Calculations')
                            ->icon(Heroicon::OutlinedShoppingCart)
                            ->schema([
                                Section::make('Items')
                                    ->description('Items are loaded from the shipment')
                                    ->schema([
                                        Placeholder::make('items_info')
                                            ->label('')
                                            ->content(fn ($record) => $record 
                                                ? sprintf('%d items from shipment %s', 
                                                    $record->shipment->items->count(),
                                                    $record->shipment->shipment_number)
                                                : 'Select a shipment to see items'
                                            ),

                                        // Items will be displayed in a repeater-like view
                                        // For now, showing as placeholder
                                    ]),

                                Section::make('Totals')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('subtotal')
                                                    ->label('Subtotal')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->disabled()
                                                    ->dehydrated(),

                                                TextInput::make('freight_cost')
                                                    ->label('Freight Cost')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->default(0),

                                                TextInput::make('insurance_cost')
                                                    ->label('Insurance Cost')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->default(0),

                                                TextInput::make('other_costs')
                                                    ->label('Other Costs')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->default(0),

                                                TextInput::make('total_value')
                                                    ->label('Total Value')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->columnSpanFull(),
                                            ]),
                                    ]),

                                Section::make('Customs Discount')
                                    ->description('Apply discount for customs declaration (affects all items)')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make('customs_discount_percentage')
                                                    ->label('Discount Percentage')
                                                    ->numeric()
                                                    ->suffix('%')
                                                    ->minValue(0)
                                                    ->maxValue(100)
                                                    ->default(0)
                                                    ->live()
                                                    ->helperText('This discount will be applied to all item unit prices in the customs version'),

                                                Placeholder::make('customs_subtotal')
                                                    ->label('Customs Subtotal')
                                                    ->content(function ($get, $record) {
                                                        if (!$record) return '$0.00';
                                                        $customsSubtotal = $record->getCustomsSubtotal();
                                                        return '$' . number_format($customsSubtotal, 2);
                                                    }),

                                                Placeholder::make('customs_total')
                                                    ->label('Customs Total Value')
                                                    ->content(function ($get, $record) {
                                                        if (!$record) return '$0.00';
                                                        $customsTotal = $record->getCustomsTotalValue();
                                                        return '$' . number_format($customsTotal, 2);
                                                    }),
                                            ]),
                                    ])
                                    ->collapsible(),
                            ]),

                        // TAB 5: Additional Info & Display Options
                        Tabs\Tab::make('Additional Info')
                            ->icon(Heroicon::OutlinedInformationCircle)
                            ->schema([
                                Section::make('Declarations & Notes')
                                    ->schema([
                                        Textarea::make('declaration')
                                            ->label('Customs Declaration')
                                            ->rows(3)
                                            ->placeholder('I declare that all information is correct...'),

                                        Textarea::make('additional_notes')
                                            ->label('Additional Notes')
                                            ->rows(3),
                                    ]),

                                Section::make('Display Options')
                                    ->description('Configure what to show/hide in PDF exports')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Toggle::make('display_options.show_exporter_tax_id')
                                                    ->label('Show Exporter Tax ID')
                                                    ->default(true),

                                                Toggle::make('display_options.show_exporter_phone')
                                                    ->label('Show Exporter Phone')
                                                    ->default(true),

                                                Toggle::make('display_options.show_exporter_email')
                                                    ->label('Show Exporter Email')
                                                    ->default(true),

                                                Toggle::make('display_options.show_importer_tax_id')
                                                    ->label('Show Importer Tax ID')
                                                    ->default(true),

                                                Toggle::make('display_options.show_importer_phone')
                                                    ->label('Show Importer Phone')
                                                    ->default(true),

                                                Toggle::make('display_options.show_importer_email')
                                                    ->label('Show Importer Email')
                                                    ->default(true),

                                                Toggle::make('display_options.show_notify_party')
                                                    ->label('Show Notify Party')
                                                    ->default(false),

                                                Toggle::make('display_options.show_bank_info')
                                                    ->label('Show Bank Information')
                                                    ->default(false),

                                                Toggle::make('display_options.show_payment_terms')
                                                    ->label('Show Payment Terms')
                                                    ->default(true),

                                                Toggle::make('display_options.show_terms_of_sale')
                                                    ->label('Show Terms of Sale')
                                                    ->default(false),

                                                Toggle::make('display_options.show_declaration')
                                                    ->label('Show Declaration')
                                                    ->default(true),

                                                Toggle::make('display_options.show_additional_notes')
                                                    ->label('Show Additional Notes')
                                                    ->default(false),

                                                Toggle::make('display_options.show_unit_weight')
                                                    ->label('Show Unit Weight')
                                                    ->default(true),

                                                Toggle::make('display_options.show_unit_volume')
                                                    ->label('Show Unit Volume')
                                                    ->default(true),

                                                Toggle::make('display_options.show_hs_code')
                                                    ->label('Show HS Code')
                                                    ->default(true),

                                                Toggle::make('display_options.show_country_of_origin')
                                                    ->label('Show Country of Origin')
                                                    ->default(true),
                                            ]),
                                    ])
                                    ->collapsible()
                                    ->collapsed(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
