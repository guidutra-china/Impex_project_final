<?php

namespace App\Filament\Resources\ExchangeRates\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ExchangeRateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Currency Pair')
                    ->schema([
                        Select::make('base_currency_id')
                            ->relationship('baseCurrency', 'code')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('From Currency')
                            ->columnSpan(1),

                        Select::make('target_currency_id')
                            ->relationship('targetCurrency', 'code')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('To Currency')
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Section::make('Rate Information')
                    ->schema([
                        TextInput::make('rate')
                            ->required()
                            ->numeric()
                            ->step(0.00000001)
                            ->minValue(0)
                            ->maxValue(999999)
                            ->helperText('How many target currency units equal 1 unit of base currency')
                            ->columnSpan(1),

                        DatePicker::make('date')
                            ->required()
                            ->default(today())
                            ->maxDate(today())
                            ->label('Effective Date')
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Section::make('Source & Status')
                    ->schema([
                        Select::make('source')
                            ->options([
                                'api' => 'API',
                                'manual' => 'Manual Entry',
                                'import' => 'Import',
                            ])
                            ->required()
                            ->default('manual')
                            ->columnSpan(1),

                        TextInput::make('source_name')
                            ->label('Source Name')
                            ->helperText('e.g., exchangerate.host, manual entry by admin')
                            ->columnSpan(1),

                        Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                            ])
                            ->required()
                            ->default('approved')
                            ->columnSpan(1),

                        Select::make('approved_by')
                            ->relationship('approvedBy', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Approved By')
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Section::make('Additional Information')
                    ->schema([
                        Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }
}
