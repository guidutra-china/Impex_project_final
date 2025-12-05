<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->label('Category Name')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', \Illuminate\Support\Str::slug($state)))
                    ->helperText('Category name must be unique'),

                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('URL-friendly identifier (auto-generated from name)'),

                Textarea::make('description')
                    ->label('Description')
                    ->rows(3)
                    ->maxLength(500)
                    ->columnSpan(2),

                Select::make('icon')
                    ->label('Icon')
                    ->options([
                        'heroicon-o-cube' => 'Cube',
                        'heroicon-o-light-bulb' => 'Light Bulb',
                        'heroicon-o-computer-desktop' => 'Computer',
                        'heroicon-o-device-phone-mobile' => 'Mobile Phone',
                        'heroicon-o-home' => 'Home',
                        'heroicon-o-wrench-screwdriver' => 'Tools',
                        'heroicon-o-beaker' => 'Beaker',
                        'heroicon-o-shopping-bag' => 'Shopping Bag',
                        'heroicon-o-gift' => 'Gift',
                        'heroicon-o-heart' => 'Heart',
                        'heroicon-o-star' => 'Star',
                        'heroicon-o-sparkles' => 'Sparkles',
                    ])
                    ->searchable()
                    ->placeholder('Select an icon'),

                ColorPicker::make('color')
                    ->label('Badge Color')
                    ->helperText('Color for category badges'),

                TextInput::make('sort_order')
                    ->label('Sort Order')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->helperText('Lower numbers appear first'),

                Checkbox::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Inactive categories won\'t appear in product selection'),
            ])
            ->columns(2);
    }
}
