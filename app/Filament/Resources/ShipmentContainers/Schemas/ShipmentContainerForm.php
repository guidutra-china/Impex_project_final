<?php

namespace App\Filament\Resources\ShipmentContainers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ShipmentContainerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Container Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('shipment_id')
                                    ->relationship('shipment', 'shipment_number')
                                    ->required()
                                    ->searchable()
                                    ->preload(),

                                TextInput::make('container_number')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('e.g., MSCU1234567'),

                                Select::make('container_type')
                                    ->options([
                                        '20ft' => '20ft',
                                        '40ft' => '40ft',
                                        '40hc' => '40hc',
                                        'pallet' => 'Pallet',
                                        'box' => 'Box',
                                    ])
                                    ->required()
                                    ->default('40ft'),

                                Select::make('status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'packed' => 'Packed',
                                        'sealed' => 'Sealed',
                                        'in_transit' => 'In Transit',
                                        'delivered' => 'Delivered',
                                    ])
                                    ->required()
                                    ->default('draft'),
                            ]),
                    ]),

                Section::make('Capacity')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('max_weight')
                                    ->numeric()
                                    ->required()
                                    ->suffix('kg')
                                    ->placeholder('e.g., 25000'),

                                TextInput::make('max_volume')
                                    ->numeric()
                                    ->required()
                                    ->suffix('m³')
                                    ->placeholder('e.g., 33.2'),

                                TextInput::make('current_weight')
                                    ->numeric()
                                    ->disabled()
                                    ->suffix('kg'),

                                TextInput::make('current_volume')
                                    ->numeric()
                                    ->disabled()
                                    ->suffix('m³'),
                            ]),
                    ]),

                Section::make('Sealing')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('seal_number')
                                    ->placeholder('e.g., SEAL123456')
                                    ->unique(ignoreRecord: true),

                                TextInput::make('sealed_at')
                                    ->type('datetime-local')
                                    ->disabled(),
                            ]),
                    ]),

                Section::make('Notes')
                    ->schema([
                        Textarea::make('notes')
                            ->rows(3),
                    ]),
            ]);
    }
}
