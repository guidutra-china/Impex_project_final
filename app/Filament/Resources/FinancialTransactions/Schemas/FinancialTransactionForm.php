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
                Section::make('Informações Básicas')
                    ->schema([
                        Textarea::make('description')
                            ->label('Descrição')
                            ->required()
                            ->maxLength(65535)
                            ->columnSpanFull(),

                        Select::make('type')
                            ->label('Tipo')
                            ->required()
                            ->options([
                                'payable' => 'Conta a Pagar',
                                'receivable' => 'Conta a Receber',
                            ])
                            ->default('payable')
                            ->live(),

                        Select::make('financial_category_id')
                            ->label('Categoria')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Valores')
                    ->schema([
                        TextInput::make('amount')
                            ->label('Valor')
                            ->required()
                            ->numeric()
                            ->prefix(fn ($get) => Currency::find($get('currency_id'))?->symbol ?? 'R$'),

                        Select::make('currency_id')
                            ->label('Moeda')
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
                            ->label('Taxa de Câmbio')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(3),

                Section::make('Datas')
                    ->schema([
                        DatePicker::make('transaction_date')
                            ->label('Data da Transação')
                            ->required()
                            ->default(now()),

                        DatePicker::make('due_date')
                            ->label('Data de Vencimento')
                            ->required()
                            ->default(now()->addDays(30)),
                    ])
                    ->columns(2),

                Section::make('Relacionamentos')
                    ->schema([
                        Select::make('supplier_id')
                            ->label('Fornecedor')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn ($get) => $get('type') === 'payable'),

                        Select::make('client_id')
                            ->label('Cliente')
                            ->relationship('client', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn ($get) => $get('type') === 'receivable'),
                    ])
                    ->columns(1),
            ]);
    }
}
