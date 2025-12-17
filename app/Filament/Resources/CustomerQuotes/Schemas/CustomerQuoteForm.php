<?php

namespace App\Filament\Resources\CustomerQuotes\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerQuoteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Quote Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('quote_number')
                                    ->label('Quote Number')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->placeholder('Auto-generated')
                                    ->columnSpan(1),

                                Select::make('order_id')
                                    ->relationship('order', 'order_number')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->label('RFQ')
                                    ->disabled(fn ($context) => $context === 'edit')
                                    ->columnSpan(1),

                                Select::make('status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'sent' => 'Sent',
                                        'viewed' => 'Viewed',
                                        'accepted' => 'Accepted',
                                        'rejected' => 'Rejected',
                                        'expired' => 'Expired',
                                    ])
                                    ->required()
                                    ->default('draft')
                                    ->columnSpan(1),

                                DatePicker::make('expires_at')
                                    ->label('Expiry Date')
                                    ->required()
                                    ->minDate(now())
                                    ->columnSpan(1),

                                Toggle::make('show_supplier_names')
                                    ->label('Show Supplier Names to Customer')
                                    ->helperText('If enabled, customer will see actual supplier names. If disabled, they will see generic labels like "Option A", "Option B".')
                                    ->default(false)
                                    ->columnSpan(2),

                                Toggle::make('show_as_unified_quote')
                                    ->label('Show as Unified Quote')
                                    ->helperText('If enabled, customer will see only selected products in a simple list without any supplier information or comparison.')
                                    ->default(false)
                                    ->columnSpan(2),
                            ]),
                    ]),

                Section::make('Customer Access')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('public_token')
                                    ->label('Public Access Token')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->placeholder('Auto-generated')
                                    ->helperText('Share this token with the customer for access without login')
                                    ->columnSpan(2),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Commission Settings')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('commission_type')
                                    ->options([
                                        'embedded' => 'Embedded in Price',
                                        'separate' => 'Separate Line Item',
                                    ])
                                    ->required()
                                    ->default('embedded')
                                    ->helperText('How commission is displayed to customer')
                                    ->columnSpan(1),

                                TextInput::make('commission_percent')
                                    ->label('Commission %')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->step(0.01)
                                    ->suffix('%')
                                    ->helperText('Commission percentage applied to supplier quotes')
                                    ->columnSpan(1),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Notes')
                    ->schema([
                        Textarea::make('internal_notes')
                            ->label('Internal Notes')
                            ->rows(3)
                            ->maxLength(1000)
                            ->helperText('Notes for internal use only (not visible to customer)')
                            ->columnSpan('full'),

                        Textarea::make('customer_notes')
                            ->label('Customer Notes')
                            ->rows(3)
                            ->maxLength(1000)
                            ->helperText('Notes visible to the customer')
                            ->columnSpan('full'),
                    ])
                    ->collapsible(),
            ]);
    }
}
