<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order Information')
                    ->schema([
                        TextInput::make('order_number')
                            ->label('Order Number')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated')
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
                    ])
                    ->columns(2),

                Section::make('Customer Information')
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
                    ])
                    ->columns(2),

                Section::make('Commission Settings')
                    ->schema([
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
                    ->schema([
                        Textarea::make('customer_notes')
                            ->label('Customer Request')
                            ->helperText('Original customer request/requirements')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('notes')
                            ->label('Internal Notes')
                            ->helperText('Internal notes (not visible to customer)')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Section::make('Summary')
                    ->schema([
                        Placeholder::make('total_amount_display')
                            ->label('Total Amount')
                            ->content(function ($record) {
                                if (!$record || !$record->total_amount) {
                                    return 'Not calculated yet';
                                }
                                $currency = $record->currency;
                                $amount = number_format($record->total_amount / 100, 2);
                                return $currency ? "{$currency->symbol}{$amount}" : "\${$amount}";
                            }),
                    ])
                    ->visibleOn('edit')
                    ->collapsible(),
            ]);
    }
}
