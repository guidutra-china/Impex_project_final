<?php

namespace App\Filament\Resources\PackingBoxes\RelationManagers;

use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PackingBoxItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'packingBoxItems';

    protected static ?string $title = 'Items in Box';

    protected static BackedEnum|string|null $icon = Heroicon::OutlinedCube;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('shipmentItem.product_sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('shipmentItem.product_name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('unit_weight')
                    ->label('Unit Weight')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' kg')
                    ->sortable(),
                TextColumn::make('total_weight')
                    ->label('Total Weight')
                    ->state(fn ($record) => $record->quantity * $record->unit_weight)
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' kg')
                    ->weight('bold')
                    ->color('success'),
                TextColumn::make('unit_volume')
                    ->label('Unit Volume')
                    ->numeric(decimalPlaces: 4)
                    ->suffix(' m³')
                    ->sortable(),
                TextColumn::make('total_volume')
                    ->label('Total Volume')
                    ->state(fn ($record) => $record->quantity * $record->unit_volume)
                    ->numeric(decimalPlaces: 4)
                    ->suffix(' m³')
                    ->weight('bold')
                    ->color('success'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->icon(Heroicon::OutlinedPlus)
                    ->modalHeading('Add Item to Box')
                    ->form($this->getForm())
                    ->after(function ($record) {
                        $record->packingBox->recalculateTotals();
                        $record->shipmentItem->updatePackedQuantity();
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->form($this->getForm())
                    ->after(function ($record) {
                        $record->packingBox->recalculateTotals();
                        $record->shipmentItem->updatePackedQuantity();
                    }),
                DeleteAction::make()
                    ->after(function ($record) {
                        $box = $record->packingBox;
                        $item = $record->shipmentItem;
                        $box->recalculateTotals();
                        $item->updatePackedQuantity();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->after(function ($records) {
                            $box = $records->first()->packingBox;
                            $box->recalculateTotals();
                            foreach ($records as $record) {
                                $record->shipmentItem->updatePackedQuantity();
                            }
                        }),
                ]),
            ]);
    }

    protected function getForm(): array
    {
        return [
            Grid::make(1)
                ->schema([
                    Select::make('shipment_item_id')
                        ->label('Select Shipment Item')
                        ->options(function () {
                            $shipmentId = $this->getOwnerRecord()->shipment_id;
                            return \App\Models\ShipmentItem::where('shipment_id', $shipmentId)
                                ->where(function ($query) {
                                    $query->where('packing_status', 'unpacked')
                                        ->orWhere('packing_status', 'partially_packed');
                                })
                                ->get()
                                ->mapWithKeys(function ($item) {
                                    return [
                                        $item->id => $item->product_sku . ' - ' . $item->product_name . 
                                                    ' (Available: ' . ($item->quantity_to_ship - $item->quantity_packed) . ')'
                                    ];
                                });
                        })
                        ->required()
                        ->searchable()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                $item = \App\Models\ShipmentItem::find($state);
                                if ($item) {
                                    $set('unit_weight', $item->unit_weight);
                                    $set('unit_volume', $item->unit_volume);
                                }
                            }
                        })
                        ->helperText('Only items with available quantity are shown'),
                    
                    TextInput::make('quantity')
                        ->label('Quantity to Pack')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->reactive()
                        ->rules([
                            function () {
                                return function (string $attribute, $value, \Closure $fail) {
                                    $shipmentItemId = request()->input('shipment_item_id');
                                    if ($shipmentItemId) {
                                        $item = \App\Models\ShipmentItem::find($shipmentItemId);
                                        $available = $item->quantity_to_ship - $item->quantity_packed;
                                        if ($value > $available) {
                                            $fail("Cannot pack more than available quantity ({$available})");
                                        }
                                    }
                                };
                            },
                        ])
                        ->helperText('Enter quantity to pack in this box'),
                    
                    TextInput::make('unit_weight')
                        ->label('Unit Weight (kg)')
                        ->numeric()
                        ->disabled()
                        ->dehydrated()
                        ->helperText('Auto-filled from shipment item'),
                    
                    TextInput::make('unit_volume')
                        ->label('Unit Volume (m³)')
                        ->numeric()
                        ->disabled()
                        ->dehydrated()
                        ->helperText('Auto-filled from shipment item'),
                ]),
        ];
    }
}
