<?php

namespace App\Filament\Resources\FinancialPayments;

use App\Filament\Resources\FinancialPayments\Pages;
use App\Models\FinancialPayment;
use App\Models\Currency;
use App\Models\ExchangeRate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FinancialPaymentResource extends Resource
{
    protected static ?string $model = FinancialPayment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static string $navigationGroup = 'Financeiro';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Pagamentos/Recebimentos';

    protected static ?string $modelLabel = 'Pagamento/Recebimento';

    protected static ?string $pluralModelLabel = 'Pagamentos/Recebimentos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações do Pagamento')
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
                                'debit' => 'Saída (Pagamento)',
                                'credit' => 'Entrada (Recebimento)',
                            ])
                            ->default('debit'),

                        Forms\Components\Select::make('bank_account_id')
                            ->label('Conta Bancária')
                            ->relationship('bankAccount', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('payment_method_id')
                            ->label('Método de Pagamento')
                            ->relationship('paymentMethod', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Data do Pagamento')
                            ->required()
                            ->default(now()),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Valores')
                    ->schema([
                        Forms\Components\TextInput::make('amount')
                            ->label('Valor')
                            ->required()
                            ->numeric()
                            ->prefix(fn ($get) => Currency::find($get('currency_id'))?->symbol ?? 'R$'),

                        Forms\Components\Select::make('currency_id')
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

                        Forms\Components\TextInput::make('fee')
                            ->label('Taxas')
                            ->numeric()
                            ->default(0)
                            ->prefix(fn ($get) => Currency::find($get('currency_id'))?->symbol ?? 'R$'),

                        Forms\Components\TextInput::make('exchange_rate_to_base')
                            ->label('Taxa de Câmbio')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Referência')
                    ->schema([
                        Forms\Components\TextInput::make('reference_number')
                            ->label('Número de Referência')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('transaction_id')
                            ->label('ID da Transação')
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Notas')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Observações')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('⚠️ Alocações')
                    ->schema([
                        Forms\Components\Placeholder::make('allocations_info')
                            ->label('')
                            ->content('As alocações para contas a pagar/receber devem ser feitas após criar o pagamento, através da ação "Alocar" na listagem.')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn ($livewire) => $livewire instanceof Pages\CreateFinancialPayment),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('payment_number')
                    ->label('Número')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tipo')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'debit' => 'Saída',
                        'credit' => 'Entrada',
                        default => $state,
                    })
                    ->colors([
                        'danger' => 'debit',
                        'success' => 'credit',
                    ]),

                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Valor')
                    ->money(fn ($record) => $record->currency->code, divideBy: 100)
                    ->sortable(),

                Tables\Columns\TextColumn::make('bankAccount.name')
                    ->label('Conta Bancária')
                    ->searchable(),

                Tables\Columns\TextColumn::make('total_allocated')
                    ->label('Alocado')
                    ->money(fn ($record) => $record->currency->code, divideBy: 100)
                    ->color('success'),

                Tables\Columns\TextColumn::make('unallocated_amount')
                    ->label('Não Alocado')
                    ->money(fn ($record) => $record->currency->code, divideBy: 100)
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'success'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendente',
                        'processing' => 'Processando',
                        'completed' => 'Concluído',
                        'failed' => 'Falhou',
                        'cancelled' => 'Cancelado',
                        default => $state,
                    })
                    ->colors([
                        'secondary' => 'pending',
                        'warning' => 'processing',
                        'success' => 'completed',
                        'danger' => 'failed',
                        'gray' => 'cancelled',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'debit' => 'Saída',
                        'credit' => 'Entrada',
                    ]),

                Tables\Filters\SelectFilter::make('bank_account_id')
                    ->label('Conta Bancária')
                    ->relationship('bankAccount', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pendente',
                        'completed' => 'Concluído',
                    ]),
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
            ->defaultSort('payment_date', 'desc');
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
            'index' => Pages\ListFinancialPayments::route('/'),
            'create' => Pages\CreateFinancialPayment::route('/create'),
            'view' => Pages\ViewFinancialPayment::route('/{record}'),
            'edit' => Pages\EditFinancialPayment::route('/{record}/edit'),
        ];
    }
}
