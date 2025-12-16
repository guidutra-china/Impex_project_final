<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Uma única seção contendo RFQ Information e Customer & Currency
                Section::make('RFQ Information')
                    ->schema([
                        Grid::make()
                            ->schema([
                                // Coluna 1 - RFQ Number
                                TextInput::make('order_number')
                                    ->label('RFQ Number')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->placeholder('Auto-generated')
                                    ->columnSpan(1),

                                // Coluna 2 - Customer
                                Select::make('customer_id')
                                    ->relationship('customer', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->required(),
                                        TextInput::make('email')
                                            ->email(),
                                    ])
                                    ->label(__('fields.customer'))
                                    ->columnSpan(1),

                                // Coluna 1 - Customer Nr. RFQ
                                TextInput::make('customer_nr_rfq')
                                    ->label('Customer Nr. RFQ')
                                    ->placeholder('Customer reference number')
                                    ->maxLength(255)
                                    ->helperText('Customer\'s reference number')
                                    ->columnSpan(1),

                                // Coluna 2 - Order Currency
                                Select::make('currency_id')
                                    ->relationship('currency', 'code', fn ($query) => $query->where('is_active', true))
                                    ->required()
                                    ->searchable()
                                    ->default(1)
                                    ->preload()
                                    ->label('Order Currency')
                                    ->helperText('Currency for customer quotes')
                                    ->columnSpan(1),

                                // Coluna 1 - Status
                                Select::make('status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'processing' => 'Processing',
                                        'quoted' => 'Quoted',
                                        'completed' => 'Completed',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->required()
                                    ->default('pending')
                                    ->columnSpan(1),

                                // Coluna 2 - Default Commission %
                                TextInput::make('commission_percent')
                                    ->label('Default Commission %')
                                    ->required()
                                    ->numeric()
                                    ->default(5.00)
                                    ->minValue(0)
                                    ->maxValue(99.99)
                                    ->step(0.01)
                                    ->suffix('%')
                                    ->helperText('Default commission for new items')
                                    ->columnSpan(1),

                                // Display average commission (read-only)
                                Placeholder::make('commission_percent_average')
                                    ->label('Average Commission')
                                    ->content(fn ($record) => $record && $record->commission_percent_average 
                                        ? number_format($record->commission_percent_average, 2) . '%' 
                                        : 'Not calculated yet')
                                    ->helperText('Weighted average across all items')
                                    ->columnSpan(1)
                                    ->hidden(fn ($record) => !$record),

                                // Coluna 1 - Tags for Suppliers
                                Select::make('tags')
                                    ->label('Tags for Suppliers')
                                    ->relationship('tags', 'name')
                                    ->multiple()
                                    ->preload()
                                    ->searchable()
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->required()
                                            ->unique('tags', 'name')
                                            ->maxLength(255),
                                    ])
                                    ->helperText('Tags help match suppliers with this RFQ')
                                    ->columnSpan(1),

                                // Coluna 2 - Commission Type
                                Select::make('commission_type')
                                    ->options([
                                        'embedded' => 'Embedded (Hidden in prices)',
                                        'separate' => 'Separate (Shown as line item)',
                                    ])
                                    ->required()
                                    ->default('embedded')
                                    ->helperText('How commission is displayed to customer')
                                    ->columnSpan(1),

                                // INCOTERMS
                                Select::make('incoterm')
                                    ->options([
                                        'EXW' => 'EXW - Ex Works',
                                        'FCA' => 'FCA - Free Carrier',
                                        'CPT' => 'CPT - Carriage Paid To',
                                        'CIP' => 'CIP - Carriage and Insurance Paid To',
                                        'DAP' => 'DAP - Delivered at Place',
                                        'DPU' => 'DPU - Delivered at Place Unloaded',
                                        'DDP' => 'DDP - Delivered Duty Paid',
                                        'FAS' => 'FAS - Free Alongside Ship',
                                        'FOB' => 'FOB - Free on Board',
                                        'CFR' => 'CFR - Cost and Freight',
                                        'CIF' => 'CIF - Cost, Insurance and Freight',
                                    ])
                                    ->searchable()
                                    ->label('INCOTERMS')
                                    ->helperText('International Commercial Terms')
                                    ->columnSpan(1),

                                TextInput::make('incoterm_location')
                                    ->label('INCOTERMS Location')
                                    ->placeholder('e.g., Shanghai Port, New York')
                                    ->helperText('Specific location for the INCOTERM')
                                    ->columnSpan(1),
                            ])
                            ->columns([
                                'default' => 1,  // 1 coluna em mobile
                                'lg' => 2,       // 2 colunas em desktop
                            ]),
                    ])
                ->columnSpanFull(),


                // Seção de Notes mantida separada
                Section::make('Notes')
                    ->schema([
                        Grid::make()
            ->schema([
                Textarea::make('customer_notes')
                    ->label('Customer Request')
                    ->helperText('Original customer request/requirements')
                    ->rows(3)
                    ->columnSpan(1),

                Textarea::make('notes')
                    ->label(__('fields.notes'))
                    ->helperText('Internal notes (not visible to customer)')
                    ->rows(3)
                    ->columnSpan(1),

                Textarea::make('quotation_instructions')
                    ->label('RFQ Quotation Instructions')
                    ->helperText('Custom instructions for this RFQ (leave empty to use company default)')
                    ->rows(5)
                    ->columnSpan(2),

                DatePicker::make('quotation_deadline')
                    ->label('Quotation Deadline')
                    ->helperText('Deadline for suppliers to submit their quotations')
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->columnSpan(1),
            ]),


                        Placeholder::make('total_amount_display')
                            ->label(__('fields.total'))
                            ->content(function ($record) {
                                if (!$record || !$record->total_amount) {
                                    return 'Not calculated yet';
                                }
                                $currency = $record->currency;
                                $amount = number_format($record->total_amount / 100, 2);
                                return $currency ? "{$currency->symbol}{$amount}" : "\${$amount}";
                            })
                            ->columnSpan(1),
                    ])
                    ->columnSpan(2),
            ]);
    }
}

