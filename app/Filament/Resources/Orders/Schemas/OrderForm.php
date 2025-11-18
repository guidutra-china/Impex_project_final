<?php

namespace App\Filament\Resources\Orders\Schemas;

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
                Section::make('RFQ Information')
                    ->schema([
                        TextInput::make('order_number')
                            ->label('RFQ Number')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated')
                            ->columnSpan(1),

                        TextInput::make('customer_nr_rfq')
                            ->label('Customer Nr. RFQ')
                            ->placeholder('Customer reference number')
                            ->maxLength(255)
                            ->helperText('Customer\'s reference number')
                            ->columnSpan(1),

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

                        Select::make('tags')
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
                    ])
                    ->columns(2),

                Section::make('Customer & Currency')
                    ->schema([
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
                            ->columnSpan(1),

                        Select::make('currency_id')
                            ->relationship('currency', 'code', fn ($query) => $query->where('is_active', true))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Order Currency')
                            ->helperText('Currency for customer quotes')
                            ->columnSpan(1),

                        TextInput::make('commission_percent')
                            ->label('Commission %')
                            ->required()
                            ->numeric()
                            ->default(5.00)
                            ->minValue(0)
                            ->maxValue(99.99)
                            ->suffix('%')
                            ->columnSpan(1),

                        Select::make('commission_type')
                            ->options([
                                'embedded' => 'Embedded (Hidden in prices)',
                                'separate' => 'Separate (Shown as line item)',
                            ])
                            ->required()
                            ->default('embedded')
                            ->helperText('How commission is displayed to customer')
                            ->columnSpan(1),

                    ])
                    ->columns(2),

                Section::make('Notes')
                    ->columns([
                        'default' => 2,  // Mobile and up
                        'sm' => 2,       // Small screens
                        'md' => 2,       // Medium screens
                        'lg' => 2,       // Large screens
                        'xl' => 2,       // Extra large screens
                    ]) // Section has 2 columns
                    ->schema([
                        Textarea::make('customer_notes')
                            ->label('Customer Request')
                            ->helperText('Original customer request/requirements')
                            ->rows(3),  // No columnSpan - let Section handle it

                        Textarea::make('notes')
                            ->label('Internal Notes')
                            ->helperText('Internal notes (not visible to customer)')
                            ->rows(3),  // No columnSpan - let Section handle it

                        Placeholder::make('total_amount_display')
                            ->label('Total Amount')
                            ->content(function ($record) {
                                // ...
                            })
                            ->columnSpan(2),  // Explicitly span both columns
                    ]),

//                        Placeholder::make('total_amount_display')
//                            ->label('Total Amount')
//                            ->content(function ($record) {
//                                if (!$record || !$record->total_amount) {
//                                    return 'Not calculated yet';
//                                }
//                                $currency = $record->currency;
//                                $amount = number_format($record->total_amount / 100, 2);
//                                return $currency ? "{$currency->symbol}{$amount}" : "\${$amount}";
//                            })
//                            ->columnSpan(2),
//                    ]),
//
            ]);
    }
}