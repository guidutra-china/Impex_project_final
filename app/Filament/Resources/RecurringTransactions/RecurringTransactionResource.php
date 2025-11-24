<?php

namespace App\Filament\Resources\RecurringTransactions;

use App\Filament\Resources\RecurringTransactions\Pages;
use App\Models\RecurringTransaction;
use App\Models\Currency;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RecurringTransactionResource extends Resource
{
    protected static ?string $model = RecurringTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static string|UnitEnum|null $navigationGroup = 'Financeiro';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Transações Recorrentes';

    protected static ?string $modelLabel = 'Transação Recorrente';

    protected static ?string $pluralModelLabel = 'Transações Recorrentes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações Básicas')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->label('Descrição')
                            ->maxLength(65535)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('type')
                            ->label('Tipo')
                            ->required()
                            ->options([
                                'payable' => 'Conta a Pagar',
                                'receivable' => 'Conta a Receber',
                            ])
                            ->default('payable')
                            ->live(),

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
                            ->prefix(fn ($get) => Currency::find($get('currency_id'))?->symbol ?? 'R$'),

                        Forms\Components\Select::make('currency_id')
                            ->label('Moeda')
                            ->relationship('currency', 'code')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Recorrência')
                    ->schema([
                        Forms\Components\Select::make('frequency')
                            ->label('Frequência')
                            ->required()
                            ->options([
                                'daily' => 'Diária',
                                'weekly' => 'Semanal',
                                'monthly' => 'Mensal',
                                'quarterly' => 'Trimestral',
                                'yearly' => 'Anual',
                            ])
                            ->default('monthly')
                            ->live(),

                        Forms\Components\TextInput::make('interval')
                            ->label('Intervalo')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->helperText('Ex: 1 = todo mês, 2 = a cada 2 meses'),

                        Forms\Components\TextInput::make('day_of_month')
                            ->label('Dia do Mês')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(31)
                            ->visible(fn ($get) => in_array($get('frequency'), ['monthly', 'quarterly', 'yearly']))
                            ->helperText('Dia do mês para gerar (1-31)'),

                        Forms\Components\Select::make('day_of_week')
                            ->label('Dia da Semana')
                            ->options([
                                0 => 'Domingo',
                                1 => 'Segunda',
                                2 => 'Terça',
                                3 => 'Quarta',
                                4 => 'Quinta',
                                5 => 'Sexta',
                                6 => 'Sábado',
                            ])
                            ->visible(fn ($get) => $get('frequency') === 'weekly')
                            ->helperText('Dia da semana para gerar'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Período')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Data de Início')
                            ->required()
                            ->default(now()),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('Data de Término')
                            ->nullable()
                            ->helperText('Deixe vazio para recorrência indefinida'),

                        Forms\Components\DatePicker::make('next_due_date')
                            ->label('Próxima Data')
                            ->required()
                            ->default(now()),

                        Forms\Components\DatePicker::make('last_generated_date')
                            ->label('Última Geração')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Configurações')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Ativa')
                            ->default(true),

                        Forms\Components\Toggle::make('auto_generate')
                            ->label('Gerar Automaticamente')
                            ->default(true)
                            ->helperText('Se desativado, deve gerar manualmente'),

                        Forms\Components\TextInput::make('days_before_due')
                            ->label('Dias Antes do Vencimento')
                            ->numeric()
                            ->default(0)
                            ->helperText('Quantos dias antes criar a transação'),
                    ])
                    ->columns(3),

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
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

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

                Tables\Columns\TextColumn::make('amount')
                    ->label('Valor')
                    ->money(fn ($record) => $record->currency->code, divideBy: 100)
                    ->sortable(),

                Tables\Columns\TextColumn::make('frequency')
                    ->label('Frequência')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'daily' => 'Diária',
                        'weekly' => 'Semanal',
                        'monthly' => 'Mensal',
                        'quarterly' => 'Trimestral',
                        'yearly' => 'Anual',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('next_due_date')
                    ->label('Próxima Data')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Ativa')
                    ->boolean(),

                Tables\Columns\TextColumn::make('last_generated_date')
                    ->label('Última Geração')
                    ->date('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'payable' => 'A Pagar',
                        'receivable' => 'A Receber',
                    ]),

                Tables\Filters\SelectFilter::make('frequency')
                    ->label('Frequência')
                    ->options([
                        'daily' => 'Diária',
                        'weekly' => 'Semanal',
                        'monthly' => 'Mensal',
                        'quarterly' => 'Trimestral',
                        'yearly' => 'Anual',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Ativa')
                    ->placeholder('Todas')
                    ->trueLabel('Apenas Ativas')
                    ->falseLabel('Apenas Inativas'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('generate')
                    ->label('Gerar Agora')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (RecurringTransaction $record) {
                        $transaction = $record->generateTransaction();
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Transação Gerada!')
                            ->body("Criada: {$transaction->transaction_number}")
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->is_active),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('next_due_date');
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
            'index' => Pages\ListRecurringTransactions::route('/'),
            'create' => Pages\CreateRecurringTransaction::route('/create'),
            'view' => Pages\ViewRecurringTransaction::route('/{record}'),
            'edit' => Pages\EditRecurringTransaction::route('/{record}/edit'),
        ];
    }
}
