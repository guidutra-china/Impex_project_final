<?php

namespace App\Filament\Resources;

use App\Models\ShipmentContainer;
use BackedEnum;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ShipmentContainerResource extends Resource
{
    protected static ?string $model = ShipmentContainer::class;

    protected static UnitEnum|string|null $navigationGroup = 'Logistics & Shipping';

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedCubeTransparent;

    protected static ?int $navigationSort = 11;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('container_number')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('container_type')
                    ->badge(),

                BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'draft',
                        'info' => 'packed',
                        'success' => 'sealed',
                        'warning' => 'in_transit',
                        'success' => 'delivered',
                    ]),

                TextColumn::make('current_weight')
                    ->label('Weight')
                    ->sortable(),

                TextColumn::make('current_volume')
                    ->label('Volume')
                    ->sortable(),

                TextColumn::make('shipment.shipment_number')
                    ->label('Shipment')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                //
            ]);
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
            'index' => \App\Filament\Resources\ShipmentContainerResource\Pages\ListShipmentContainers::class,
            'create' => \App\Filament\Resources\ShipmentContainerResource\Pages\CreateShipmentContainer::class,
            'edit' => \App\Filament\Resources\ShipmentContainerResource\Pages\EditShipmentContainer::class,
        ];
    }
}
