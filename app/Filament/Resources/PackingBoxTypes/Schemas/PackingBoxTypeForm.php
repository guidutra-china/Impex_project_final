<?php

namespace App\Filament\Resources\PackingBoxTypes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PackingBoxTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., Standard Carton Box')
                                    ->unique(ignoreRecord: true),

                                TextInput::make('code')
                                    ->label('Code')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., STD-BOX-001')
                                    ->unique(ignoreRecord: true)
                                    ->helperText('Short code for identification'),
                            ]),

                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(65535)
                            ->placeholder('Additional details about this box type...'),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive types will not be available for selection'),
                    ]),

                Section::make('Dimensions')
                    ->description('All dimensions in centimeters (cm)')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('length')
                                    ->label('Length (cm)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->suffix('cm')
                                    ->placeholder('60'),

                                TextInput::make('width')
                                    ->label('Width (cm)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->suffix('cm')
                                    ->placeholder('40'),

                                TextInput::make('height')
                                    ->label('Height (cm)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->suffix('cm')
                                    ->placeholder('40'),
                            ]),
                    ]),

                Section::make('Capacity')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('max_weight')
                                    ->label('Max Weight (kg)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->suffix('kg')
                                    ->placeholder('25')
                                    ->helperText('Maximum gross weight'),

                                TextInput::make('max_volume')
                                    ->label('Max Volume (m³)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.0001)
                                    ->suffix('m³')
                                    ->placeholder('0.096')
                                    ->helperText('Maximum internal volume'),

                                TextInput::make('tare_weight')
                                    ->label('Tare Weight (kg)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->suffix('kg')
                                    ->placeholder('0.5')
                                    ->helperText('Empty box weight'),
                            ]),
                    ]),

                Section::make('Pricing')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('unit_cost')
                                    ->label('Unit Cost')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->prefix('$')
                                    ->placeholder('0.00')
                                    ->helperText('Cost per box'),

                                Select::make('currency_id')
                                    ->label('Currency')
                                    ->relationship('currency', 'code')
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Currency for unit cost'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
