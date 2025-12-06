<?php

namespace App\Filament\Resources\CommercialInvoices\Schemas;

use App\Models\Client;
use App\Models\Currency;
use App\Models\PaymentTerm;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\CommercialInvoice;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class CommercialInvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Tabs')
                    ->tabs([
                        Tabs\Tab::make('Basic Information')
                            ->schema(static::getBasicInfoComponents())
                            ->columns(2),

                        Tabs\Tab::make('Parties')
                            ->schema(static::getPartiesComponents())
                            ->columns(2),

                        Tabs\Tab::make('Shipping Details')
                            ->schema(static::getShippingComponents())
                            ->columns(2),

                        Tabs\Tab::make('Items')
                            ->schema(static::getItemsComponents()),

                        Tabs\Tab::make('Customs & Payment')
                            ->schema(static::getCustomsPaymentComponents())
                            ->columns(2),

                        Tabs\Tab::make('Display Options')
                            ->schema(static::getDisplayOptionsComponents())
                            ->columns(2),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    protected static function getBasicInfoComponents(): array
    {
        return [
            TextInput::make('invoice_number')
                ->label('Invoice Number')
                ->default(fn () => CommercialInvoice::generateInvoiceNumber())
                ->disabled()
                ->dehydrated()
                ->required(),

            Select::make('shipment_id')
                ->label('Shipment')
                ->relationship('shipment', 'shipment_number')
                ->searchable()
                ->preload()
                ->required()
                ->live()
                ->afterStateUpdated(function (Set $set, ?string $state) {
                    if ($state) {
                        $shipment = Shipment::find($state);
                        if ($shipment) {
                            // Basic info
                            $set('client_id', $shipment->customer_id);
                            $set('shipment_date', $shipment->actual_departure_date ?? $shipment->estimated_departure_date);
                            
                            // Shipping details
                            $set('port_of_loading', $shipment->port_of_loading);
                            $set('port_of_discharge', $shipment->port_of_discharge);
                            $set('final_destination', $shipment->final_destination);
                            $set('bl_number', $shipment->bl_number ?? '');
                            
                            // Container numbers
                            $containerNumbers = $shipment->containers()->pluck('container_number')->join(', ');
                            $set('container_numbers', $containerNumbers);
                            
                            // Get proforma invoice if exists
                            $proforma = $shipment->proformaInvoices()->first();
                            if ($proforma) {
                                $set('proforma_invoice_id', $proforma->id);
                                $set('currency_id', $proforma->currency_id);
                                $set('payment_term_id', $proforma->payment_term_id);
                                $set('incoterm', $proforma->incoterm);
                                $set('incoterm_location', $proforma->incoterm_location);
                            }
                            
                            // Exporter details from Company Settings
                            $companySettings = \App\Models\CompanySetting::current();
                            if ($companySettings) {
                                $set('exporter_name', $companySettings->company_name);
                                $set('exporter_address', $companySettings->full_address);
                                $set('exporter_tax_id', $companySettings->tax_id);
                                $set('exporter_country', $companySettings->country);
                                
                                // Bank details
                                $set('bank_name', $companySettings->bank_name);
                                $set('bank_account', $companySettings->bank_account_number);
                                $set('bank_swift', $companySettings->bank_swift_code);
                            }
                            
                            // Importer details from Customer
                            $customer = $shipment->customer;
                            if ($customer) {
                                $set('importer_name', $customer->name);
                                // Build full address
                                $addressParts = array_filter([
                                    $customer->address,
                                    $customer->city,
                                    $customer->state . ' ' . $customer->zip,
                                    $customer->country,
                                ]);
                                $set('importer_address', implode(', ', $addressParts));
                                $set('importer_tax_id', $customer->tax_number ?? '');
                                $set('importer_country', $customer->country ?? '');
                            }
                        }
                    }
                }),

            Select::make('client_id')
                ->label('Customer')
                ->relationship('client', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->disabled(),

            Select::make('proforma_invoice_id')
                ->label('Proforma Invoice')
                ->relationship('proformaInvoice', 'proforma_number')
                ->searchable()
                ->preload(),

            DatePicker::make('invoice_date')
                ->label('Invoice Date')
                ->default(now())
                ->required(),

            DatePicker::make('shipment_date')
                ->label('Shipment Date'),

            DatePicker::make('due_date')
                ->label('Due Date'),

            Select::make('status')
                ->label('Status')
                ->options([
                    'draft' => 'Draft',
                    'issued' => 'Issued',
                    'sent' => 'Sent',
                    'paid' => 'Paid',
                    'cancelled' => 'Cancelled',
                ])
                ->default('draft')
                ->required(),
        ];
    }

    protected static function getPartiesComponents(): array
    {
        return [
            Section::make('Exporter Details')
                ->schema([
                    TextInput::make('exporter_name')
                        ->label('Company Name')
                        ->maxLength(255),

                    Textarea::make('exporter_address')
                        ->label('Address')
                        ->rows(3),

                    TextInput::make('exporter_tax_id')
                        ->label('Tax ID / VAT Number')
                        ->maxLength(100),

                    TextInput::make('exporter_country')
                        ->label('Country')
                        ->maxLength(100)
                        ->placeholder('Hong Kong, China, Brazil, etc.'),
                ])
                ->columns(1)
                ->collapsible(),

            Section::make('Importer Details')
                ->schema([
                    TextInput::make('importer_name')
                        ->label('Company Name')
                        ->maxLength(255),

                    Textarea::make('importer_address')
                        ->label('Address')
                        ->rows(3),

                    TextInput::make('importer_tax_id')
                        ->label('Tax ID / VAT Number')
                        ->maxLength(100),

                    TextInput::make('importer_country')
                        ->label('Country')
                        ->maxLength(100)
                        ->placeholder('Portugal, United States, etc.'),
                ])
                ->columns(1)
                ->collapsible(),
        ];
    }

    protected static function getShippingComponents(): array
    {
        return [
            TextInput::make('incoterm')
                ->label('Incoterm')
                ->maxLength(10)
                ->placeholder('FOB, CIF, EXW, etc.'),

            TextInput::make('incoterm_location')
                ->label('Incoterm Location')
                ->maxLength(255),

            TextInput::make('port_of_loading')
                ->label('Port of Loading')
                ->maxLength(255),

            TextInput::make('port_of_discharge')
                ->label('Port of Discharge')
                ->maxLength(255),

            TextInput::make('final_destination')
                ->label('Final Destination')
                ->maxLength(255),

            TextInput::make('bl_number')
                ->label('B/L Number')
                ->maxLength(100),

            Textarea::make('container_numbers')
                ->label('Container Numbers')
                ->placeholder('Comma-separated list')
                ->rows(2)
                ->columnSpanFull(),
        ];
    }

    protected static function getItemsComponents(): array
    {
        return [
            Repeater::make('items')
                ->relationship()
                ->schema([
                    Select::make('product_id')
                        ->label('Product')
                        ->relationship('product', 'name')
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function (Set $set, ?string $state) {
                            if ($state) {
                                $product = Product::find($state);
                                if ($product) {
                                    $set('description', $product->name);
                                    $set('hs_code', $product->hs_code ?? '');
                                    $set('country_of_origin', $product->country_of_origin ?? '');
                                    $set('unit_price', $product->price ?? 0);
                                }
                            }
                        })
                        ->columnSpan(2),

                    TextInput::make('description')
                        ->label('Description')
                        ->required()
                        ->columnSpan(2),

                    TextInput::make('hs_code')
                        ->label('HS Code')
                        ->maxLength(20),

                    TextInput::make('country_of_origin')
                        ->label('Origin')
                        ->maxLength(2)
                        ->placeholder('CN, US, etc.'),

                    TextInput::make('quantity')
                        ->label('Quantity')
                        ->numeric()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            $quantity = (float) $get('quantity');
                            $unitPrice = (float) $get('unit_price');
                            $set('total', $quantity * $unitPrice);
                        }),

                    TextInput::make('unit')
                        ->label('Unit')
                        ->default('PCS')
                        ->maxLength(20),

                    TextInput::make('unit_price')
                        ->label('Unit Price')
                        ->numeric()
                        ->prefix('$')
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            $quantity = (float) $get('quantity');
                            $unitPrice = (float) $get('unit_price');
                            $set('total', $quantity * $unitPrice);
                        }),

                    TextInput::make('total')
                        ->label('Total')
                        ->numeric()
                        ->prefix('$')
                        ->disabled()
                        ->dehydrated()
                        ->required(),

                    TextInput::make('weight')
                        ->label('Weight (kg)')
                        ->numeric()
                        ->step(0.001),

                    TextInput::make('volume')
                        ->label('Volume (m³)')
                        ->numeric()
                        ->step(0.0001),

                    Textarea::make('notes')
                        ->label('Notes')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(4)
                ->defaultItems(1)
                ->reorderable()
                ->collapsible()
                ->itemLabel(fn (array $state): ?string => $state['description'] ?? null),
        ];
    }

    protected static function getCustomsPaymentComponents(): array
    {
        return [
            Section::make('Customs Discount')
                ->schema([
                    TextInput::make('customs_discount_percentage')
                        ->label('Customs Discount (%)')
                        ->numeric()
                        ->suffix('%')
                        ->default(0)
                        ->minValue(0)
                        ->maxValue(100)
                        ->helperText('Percentage discount applied to ALL items for customs declaration'),

                    Placeholder::make('customs_info')
                        ->label('Customs Calculation')
                        ->content(function (?CommercialInvoice $record) {
                            if (!$record || !$record->hasCustomsDiscount()) {
                                return 'No customs discount applied';
                            }
                            
                            $original = number_format($record->total, 2);
                            $customs = number_format($record->getCustomsTotal(), 2);
                            $discount = number_format($record->total - $record->getCustomsTotal(), 2);
                            
                            return "Original: \${$original} | Customs: \${$customs} | Discount: \${$discount}";
                        }),
                ])
                ->columns(1),

            Section::make('Payment Information')
                ->schema([
                    Select::make('currency_id')
                        ->label('Currency')
                        ->relationship('currency', 'code')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('payment_term_id')
                        ->label('Payment Terms')
                        ->relationship('paymentTerm', 'name')
                        ->searchable()
                        ->preload(),

                    TextInput::make('payment_method')
                        ->label('Payment Method')
                        ->maxLength(255),

                    TextInput::make('payment_reference')
                        ->label('Payment Reference')
                        ->maxLength(255),
                ])
                ->columns(2),

            Section::make('Bank Details')
                ->schema([
                    TextInput::make('bank_name')
                        ->label('Bank Name')
                        ->maxLength(255),

                    TextInput::make('bank_account')
                        ->label('Account Number')
                        ->maxLength(255),

                    TextInput::make('bank_swift')
                        ->label('SWIFT/BIC Code')
                        ->maxLength(20),

                    Textarea::make('bank_address')
                        ->label('Bank Address')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->collapsible(),

            Section::make('Totals')
                ->schema([
                    Placeholder::make('subtotal')
                        ->label('Subtotal')
                        ->content(fn (?CommercialInvoice $record): string => 
                            $record ? '$' . number_format($record->subtotal, 2) : '$0.00'
                        ),

                    Placeholder::make('tax')
                        ->label('Tax')
                        ->content(fn (?CommercialInvoice $record): string => 
                            $record ? '$' . number_format($record->tax, 2) : '$0.00'
                        ),

                    Placeholder::make('total')
                        ->label('Total')
                        ->content(fn (?CommercialInvoice $record): string => 
                            $record ? '$' . number_format($record->total, 2) : '$0.00'
                        ),

                    Placeholder::make('customs_total')
                        ->label('Customs Total')
                        ->content(fn (?CommercialInvoice $record): string => 
                            $record && $record->hasCustomsDiscount() 
                                ? '$' . number_format($record->getCustomsTotal(), 2) 
                                : 'N/A'
                        ),
                ])
                ->columns(4),
        ];
    }

    protected static function getDisplayOptionsComponents(): array
    {
        return [
            Section::make('PDF Display Options')
                ->description('Control what information appears in the generated PDF documents')
                ->schema([
                    Toggle::make('display_options.show_payment_terms')
                        ->label('Show Payment Terms')
                        ->default(true),

                    Toggle::make('display_options.show_bank_info')
                        ->label('Show Bank Information')
                        ->default(true),

                    Toggle::make('display_options.show_exporter_details')
                        ->label('Show Exporter Details')
                        ->default(true),

                    Toggle::make('display_options.show_importer_details')
                        ->label('Show Importer Details')
                        ->default(true),

                    Toggle::make('display_options.show_shipping_details')
                        ->label('Show Shipping Details')
                        ->default(true),

                    Toggle::make('display_options.show_hs_codes')
                        ->label('Show HS Codes')
                        ->default(true),

                    Toggle::make('display_options.show_country_of_origin')
                        ->label('Show Country of Origin')
                        ->default(true),

                    Toggle::make('display_options.show_weight_volume')
                        ->label('Show Weight & Volume')
                        ->default(true),
                ])
                ->columns(2),

            Section::make('Notes & Terms')
                ->schema([
                    Textarea::make('notes')
                        ->label('Internal Notes')
                        ->rows(3)
                        ->columnSpanFull(),

                    Textarea::make('terms_and_conditions')
                        ->label('Terms and Conditions')
                        ->rows(5)
                        ->columnSpanFull(),
                ])
                ->columns(1),
        ];
    }
}
