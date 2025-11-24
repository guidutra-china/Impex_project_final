<?php

namespace App\Filament\Resources\FinancialCategories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FinancialCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),

                        Select::make('type')
                            ->label('Tipo')
                            ->required()
                            ->options([
                                'expense' => 'Despesa',
                                'revenue' => 'Receita',
                                'exchange_variation' => 'Variação Cambial',
                            ])
                            ->default('expense'),

                        Select::make('parent_id')
                            ->label('Categoria Pai')
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Toggle::make('is_system')
                            ->label('Categoria do Sistema')
                            ->helperText('Categorias do sistema não podem ser deletadas')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),
            ]);
    }
}
