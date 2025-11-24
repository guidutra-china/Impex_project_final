<?php

namespace App\Filament\Resources\FinancialTransactions;

use App\Filament\Resources\FinancialTransactions\Pages;
use App\Models\FinancialTransaction;
use App\Models\Currency;
use App\Models\ExchangeRate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Colors\Color;

class FinancialTransactionResource extends Resource
{
    protected static ?string $model = FinancialTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static string $navigationGroup = 'Financeiro';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Contas a Pagar/Receber';

    protected static ?string $modelLabel = 'Transação Financeira';

    protected static ?string $pluralModelLabel = 'Transações Financeiras';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações Básicas')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Descrição')
                            ->required()
                            ->maxLength(65535)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('type')
                            ->label('Tipo')
                            ->required()
                            ->options([
                                'payable' => 'Conta a Pagar',
                                'receivable' => 'Conta a Receber',
                            ])
                            ->live()
                            ->default('payable'),

                        Forms\Components\Select::make('financial_category_id')
                            ->label('Categoria')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Valores')
                    ->schema([
                        Forms\Components\TextInput::make('amount')
                            ->label('Valor')
                            ->required()
                            ->numeric()
                            ->prefix(fn ($get) => Currency::find($get('currency_id'))?->symbol ?? 'R$')
                            ->helperText('Valor em centavos será calculado automaticamente')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                // Convert to centavos
                                $set('amount', (int) ($state * 100));
                                
                                // Calculate base currency amount
                                $currencyId = $get('currency_id');
                                $rate = $get('exchange_rate_to_base') ?? 1;
                                
                                if ($currencyId && $rate) {
                                    $amountBase = (int) ($state * 100 * $rate);
                                    $set('amount_base_currency', $amountBase);
                                }
                            }),

                        Forms\Components\Select::make('currency_id')
                            ->label('Moeda')
                            ->relationship('currency', 'code')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                if (!$state) return;
                                
                                // Get base currency
                                $baseCurrency = Currency::where('is_base', true)->first();
                                if (!$baseCurrency) return;
                                
                                // Get exchange rate
                                $rate = ExchangeRate::getConversionRate(
                                    $state,
                                    $baseCurrency->id,
                                    now()->toDateString()
                                );
                                
                                $set('exchange_rate_to_base', $rate ?? 1.0);
                                
                                // Recalculate base currency amount
                                $amount = $get('amount');
                                if ($amount) {
                                    $amountBase = (int) ($amount * ($rate ?? 1.0));
                                    $set('amount_base_currency', $amountBase);
                                }
                            }),

                        Forms\Components\TextInput::make('exchange_rate_to_base')
                            ->label('Taxa de Câmbio')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Taxa para moeda base'),

                        Forms\Components\TextInput::make('amount_base_currency')
                            ->label('Valor na Moeda Base')
                            ->disabled()
                            ->dehydrated()
                            ->formatStateUsing(fn ($state) => $state ? number_format($state / 100, 2) : '0.00')
                            ->prefix('R$'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Datas')
                    ->schema([
                        Forms\Components\DatePicker::make('transaction_date')
                            ->label('Data da Transação')
                            ->required()
                            ->default(now()),

                        Forms\Components\DatePicker::make('due_date')
                            ->label('Data de Vencimento')
                            ->required()
                            ->default(now()->addDays(30)),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Relacionamentos')
                    ->schema([
                        Forms\Components\Select::make('supplier_id')
                            ->label('Fornecedor')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn ($get) => $get('type') === 'payable'),

                        Forms\Components\Select::make('client_id')
                            ->label('Cliente')
                            ->relationship('client', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn ($get) => $get('type') === 'receivable'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Notas')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Observações')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_number')
                    ->label('Número')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Descrição')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->description),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tipo')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'payable' => 'A Pagar',
                        'receivable' => 'A Receber',
                        default => $state,
                    })
                    ->colors([
                        'danger' => 'payable',
                        'success' => 'receivable',
                    ]),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendente',
                        'partially_paid' => 'Parcial',
                        'paid' => 'Pago',
                        'overdue' => 'Vencido',
                        'cancelled' => 'Cancelado',
                        default => $state,
                    })
                    ->colors([
                        'secondary' => 'pending',
                        'warning' => 'partially_paid',
                        'success' => 'paid',
                        'danger' => 'overdue',
                        'gray' => 'cancelled',
                    ]),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Valor')
                    ->money(fn ($record) => $record->currency->code, divideBy: 100)
                    ->sortable(),

                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('Saldo')
                    ->money(fn ($record) => $record->currency->code, divideBy: 100)
                    ->color(fn ($record) => $record->remaining_amount > 0 ? 'warning' : 'success'),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('days_until_due')
                    ->label('Dias')
                    ->formatStateUsing(fn ($state) => $state > 0 ? "+{$state}" : $state)
                    ->color(fn ($state) => match (true) {
                        $state < 0 => 'danger',
                        $state < 7 => 'warning',
                        default => 'success',
                    })
                    ->tooltip(fn ($state) => $state > 0 ? "Vence em {$state} dias" : "Venceu há " . abs($state) . " dias"),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Categoria')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'payable' => 'A Pagar',
                        'receivable' => 'A Receber',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pendente',
                        'partially_paid' => 'Parcial',
                        'paid' => 'Pago',
                        'overdue' => 'Vencido',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('financial_category_id')
                    ->label('Categoria')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('overdue')
                    ->label('Apenas Vencidas')
                    ->query(fn ($query) => $query->overdue()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->status === 'pending'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('due_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFinancialTransactions::route('/'),
            'create' => Pages\CreateFinancialTransaction::route('/create'),
            'view' => Pages\ViewFinancialTransaction::route('/{record}'),
            'edit' => Pages\EditFinancialTransaction::route('/{record}/edit'),
        ];
    }
}
