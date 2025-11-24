<?php

namespace App\Filament\Resources\FinancialCategories;

use App\Filament\Resources\FinancialCategories\Pages;
use App\Models\FinancialCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FinancialCategoryResource extends Resource
{
    protected static ?string $model = FinancialCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static string $navigationGroup = 'Financeiro';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Categorias';

    protected static ?string $modelLabel = 'Categoria Financeira';

    protected static ?string $pluralModelLabel = 'Categorias Financeiras';

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

                        Forms\Components\TextInput::make('code')
                            ->label('Código')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20)
                            ->helperText('Ex: COST-FIX-RENT'),

                        Forms\Components\Select::make('type')
                            ->label('Tipo')
                            ->required()
                            ->options([
                                'expense' => 'Despesa',
                                'revenue' => 'Receita',
                                'exchange_variation' => 'Variação Cambial',
                            ])
                            ->default('expense'),

                        Forms\Components\Textarea::make('description')
                            ->label('Descrição')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Hierarquia')
                    ->schema([
                        Forms\Components\Select::make('parent_id')
                            ->label('Categoria Pai')
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Deixe vazio para categoria raiz'),
                    ]),

                Forms\Components\Section::make('Configurações')
                    ->schema([
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Ordem')
                            ->numeric()
                            ->default(0)
                            ->helperText('Ordem de exibição'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Ativa')
                            ->default(true),

                        Forms\Components\Toggle::make('is_system')
                            ->label('Categoria do Sistema')
                            ->default(false)
                            ->helperText('Categorias do sistema não podem ser deletadas')
                            ->disabled(fn ($record) => $record?->is_system),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nome Completo')
                    ->searchable(['name'])
                    ->sortable(['name'])
                    ->description(fn (FinancialCategory $record) => $record->code),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tipo')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'expense' => 'Despesa',
                        'revenue' => 'Receita',
                        'exchange_variation' => 'Variação Cambial',
                        default => $state,
                    })
                    ->colors([
                        'danger' => 'expense',
                        'success' => 'revenue',
                        'warning' => 'exchange_variation',
                    ]),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Ativa')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_system')
                    ->label('Sistema')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('transactions_count')
                    ->label('Transações')
                    ->counts('transactions')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'expense' => 'Despesa',
                        'revenue' => 'Receita',
                        'exchange_variation' => 'Variação Cambial',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Ativa')
                    ->placeholder('Todas')
                    ->trueLabel('Apenas Ativas')
                    ->falseLabel('Apenas Inativas'),

                Tables\Filters\TernaryFilter::make('is_system')
                    ->label('Sistema')
                    ->placeholder('Todas')
                    ->trueLabel('Apenas Sistema')
                    ->falseLabel('Apenas Customizadas'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Tables\Actions\DeleteAction $action, FinancialCategory $record) {
                        if (!$record->canBeDeleted()) {
                            $action->cancel();
                            
                            if ($record->is_system) {
                                $action->failureNotificationTitle('Categoria do sistema não pode ser deletada');
                            } elseif ($record->transactions()->exists()) {
                                $action->failureNotificationTitle('Categoria possui transações vinculadas');
                            } elseif ($record->children()->exists()) {
                                $action->failureNotificationTitle('Categoria possui sub-categorias');
                            }
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order');
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
            'index' => Pages\ListFinancialCategories::route('/'),
            'create' => Pages\CreateFinancialCategory::route('/create'),
            'view' => Pages\ViewFinancialCategory::route('/{record}'),
            'edit' => Pages\EditFinancialCategory::route('/{record}/edit'),
        ];
    }
}
