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
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                // Auto-generate code if empty
                                if (!$get('code') && $state) {
                                    $code = strtoupper(substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($state)), 0, 10));
                                    $set('code', $code);
                                }
                            }),

                        TextInput::make('code')
                            ->label('Code')
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true)
                            ->helperText('Auto-generated from name. You can edit if needed.')
                            ->placeholder('Auto-generated'),

                        Select::make('type')
                            ->label('Type')
                            ->required()
                            ->options([
                                'expense' => 'Expense',
                                'revenue' => 'Revenue',
                                'exchange_variation' => 'Exchange Variation',
                            ])
                            ->default('expense'),

                        Select::make('parent_id')
                            ->label('Parent Category')
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Toggle::make('is_system')
                            ->label('System Category')
                            ->helperText('System categories cannot be deleted')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),
            ]);
    }
}
