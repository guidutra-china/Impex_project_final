<?php

namespace App\Filament\Resources;
use UnitEnum;

use App\Models\ShipmentContainer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;

class ShipmentContainerResource extends Resource
{
    protected static ?string $model = ShipmentContainer::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube-transparent';

    protected static UnitEnum|string|null $navigationGroup = 'Shipments';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informações do Container')
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
                                    ->placeholder('ex: MSCU1234567'),

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

                Section::make('Capacidade')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('max_weight')
                                    ->numeric()
                                    ->required()
                                    ->suffix('kg')
                                    ->placeholder('ex: 25000'),

                                TextInput::make('max_volume')
                                    ->numeric()
                                    ->required()
                                    ->suffix('m³')
                                    ->placeholder('ex: 33.2'),

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

                Section::make('Selagem')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('seal_number')
                                    ->placeholder('ex: SEAL123456')
                                    ->unique(ignoreRecord: true),

                                TextInput::make('sealed_at')
                                    ->type('datetime-local')
                                    ->disabled(),
                            ]),
                    ]),

                Section::make('Notas')
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
                    ->formatStateUsing(fn($state) => "{$state} / {$this->max_weight} kg")
                    ->sortable(),

                TextColumn::make('current_volume')
                    ->label('Volume')
                    ->formatStateUsing(fn($state) => "{$state} / {$this->max_volume} m³")
                    ->sortable(),

                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items'),

                TextColumn::make('shipment.shipment_number')
                    ->label('Shipment')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'packed' => 'Packed',
                        'sealed' => 'Sealed',
                        'in_transit' => 'In Transit',
                        'delivered' => 'Delivered',
                    ]),

                Tables\Filters\SelectFilter::make('container_type')
                    ->options([
                        '20ft' => '20ft',
                        '40ft' => '40ft',
                        '40hc' => '40hc',
                        'pallet' => 'Pallet',
                        'box' => 'Box',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
