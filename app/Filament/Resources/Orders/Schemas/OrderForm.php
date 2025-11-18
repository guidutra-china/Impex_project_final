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
                // Grid para colocar RFQ Information e Customer & Currency lado a lado
                Grid::make()
                    ->schema([
                        // RFQ Information - Coluna 1
                        Section::make('RFQ Information')
                            ->schema([
                                TextInput::make('order_number')
                                    ->label('RFQ Number')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->placeholder('Auto-generated'),

                                TextInput::make('customer_nr_rfq')
                                    ->label('Customer Nr. RFQ')
                                    ->placeholder('Customer reference number')
                                    ->maxLength(255)
                                    ->helperText('Customer\'s reference number'),

                                Select::make('status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'processing' => 'Processing',
                                        'quoted' => 'Quoted',
                                        'completed' => 'Completed',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->required()
                                    ->default('pending'),

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
                                    ->helperText('Tags help match suppliers with this RFQ'),
                            ])
                            ->columns(1),

                        // Customer & Currency - Coluna 2
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
                                    ]),

                                Select::make('currency_id')
                                    ->relationship('currency', 'code', fn ($query) => $query->where('is_active', true))
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->label('Order Currency')
                                    ->helperText('Currency for customer quotes'),

                                TextInput::make('commission_percent')
                                    ->label('Commission %')
                                    ->required()
                                    ->numeric()
                                    ->default(5.00)
                                    ->minValue(0)
                                    ->maxValue(99.99)
                                    ->step(0.01)
                                    ->suffix('%'),

                                Select::make('commission_type')
                                    ->options([
                                        'embedded' => 'Embedded (Hidden in prices)',
                                        'separate' => 'Separate (Shown as line item)',
                                    ])
                                    ->required()
                                    ->default('embedded')
                                    ->helperText('How commission is displayed to customer'),
                            ])
                            ->columns(1),
                    ])
                    ->columns([
                        'default' => 1,  // 1 coluna em mobile
                        'lg' => 2,       // 2 colunas em desktop
                    ]),

                // Seção de Notes mantida separada
                Section::make('Notes')
                    ->schema([
                        Textarea::make('customer_notes')
                            ->label('Customer Request')
                            ->helperText('Original customer request/requirements')
                            ->rows(3)
                            ->columnSpan(1),

                        Textarea::make('notes')
                            ->label('Internal Notes')
                            ->helperText('Internal notes (not visible to customer)')
                            ->rows(3)
                            ->columnSpan(1),

                        Placeholder::make('total_amount_display')
                            ->label('Total Amount')
                            ->content(function ($record) {
                                if (!$record || !$record->total_amount) {
                                    return 'Not calculated yet';
                                }
                                $currency = $record->currency;
                                $amount = number_format($record->total_amount / 100, 2);
                                return $currency ? "{$currency->symbol}{$amount}" : "\${$amount}";
                            })
                            ->columnSpan(2),
                    ])
                    ->columns(2),
            ]);
    }
}
