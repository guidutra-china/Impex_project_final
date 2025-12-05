<?php

namespace App\Filament\Resources\PackingUnits\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContainerTypeForm
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
                                    ->placeholder('e.g., 20ft Standard Container')
                                    ->unique(ignoreRecord: true),

                                TextInput::make('code')
                                    ->label('Code')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., 20ft, 40ft, 40hc')
                                    ->unique(ignoreRecord: true)
                                    ->helperText('Short code for identification'),
                            ]),

                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(65535)
                            ->placeholder('Additional details about this container type...'),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive types will not be available for selection'),
                    ]),

                Section::make('Dimensions')
                    ->description('All dimensions in meters')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('length')
                                    ->label('Length (m)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->suffix('m')
                                    ->placeholder('5.90'),

                                TextInput::make('width')
                                    ->label('Width (m)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->suffix('m')
                                    ->placeholder('2.35'),

                                TextInput::make('height')
                                    ->label('Height (m)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->suffix('m')
                                    ->placeholder('2.39'),
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
                                    ->placeholder('28,000')
                                    ->helperText('Maximum gross weight'),

                                TextInput::make('max_volume')
                                    ->label('Max Volume (m³)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.0001)
                                    ->suffix('m³')
                                    ->placeholder('33.2')
                                    ->helperText('Maximum internal volume'),

                                TextInput::make('tare_weight')
                                    ->label('Tare Weight (kg)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->suffix('kg')
                                    ->placeholder('2,300')
                                    ->helperText('Empty container weight'),
                            ]),
                    ]),

                Section::make('Pricing')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('base_cost')
                                    ->label('Base Cost')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->prefix('$')
                                    ->placeholder('0.00')
                                    ->helperText('Base cost for this container type'),

                                Select::make('currency_id')
                                    ->label('Currency')
                                    ->relationship('currency', 'code')
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Currency for base cost'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
