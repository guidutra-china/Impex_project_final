<?php

namespace App\Filament\Resources\FinancialTransactions\Schemas;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FinancialTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Information')
                    ->schema([
                        Textarea::make('description')
                            ->label(__('fields.description'))
                            ->required()
                            ->maxLength(65535)
                            ->columnSpanFull(),

                        Select::make('type')
                            ->label(__('fields.type'))
                            ->required()
                            ->options([
                                'payable' => 'Payable',
                                'receivable' => 'Receivable',
                            ])
                            ->default('payable')
                            ->live(),

                        Select::make('financial_category_id')
                            ->label(__('fields.category'))
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Values')
                    ->schema([
                        TextInput::make('amount')
                            ->label(__('fields.amount'))
                            ->required()
                            ->numeric()
                            ->prefix(fn ($get) => Currency::find($get('currency_id'))?->symbol ?? '$'),

                        Select::make('currency_id')
                            ->label(__('fields.currency'))
                            ->relationship('currency', 'code')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (!$state) return;
                                
                                $baseCurrency = Currency::where('is_base', true)->first();
                                if (!$baseCurrency) return;
                                
                                $rate = ExchangeRate::getConversionRate(
                                    $state,
                                    $baseCurrency->id,
                                    now()->toDateString()
                                );
                                
                                $set('exchange_rate_to_base', $rate ?? 1.0);
                            }),

                        TextInput::make('exchange_rate_to_base')
                            ->label(__('fields.exchange_rate'))
                            ->numeric()
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(3),

                Section::make('Dates')
                    ->schema([
                        DatePicker::make('transaction_date')
                            ->label('Transaction Date')
                            ->required()
                            ->default(now()),

                        DatePicker::make('due_date')
                            ->label(__('fields.due_date'))
                            ->required()
                            ->default(now()->addDays(30)),
                    ])
                    ->columns(2),

                Section::make('Relationships')
                    ->schema([
                        Select::make('supplier_id')
                            ->label(__('fields.supplier'))
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn ($get) => $get('type') === 'payable'),

                        Select::make('client_id')
                            ->label(__('fields.customer'))
                            ->relationship('client', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn ($get) => $get('type') === 'receivable'),
                    ])
                    ->columns(1),
            ]);
    }
}
