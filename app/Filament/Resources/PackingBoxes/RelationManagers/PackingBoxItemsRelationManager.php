<?php

namespace App\Filament\Resources\PackingBoxes\RelationManagers;

use App\Models\ShipmentItem;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Schemas\Schema;

class PackingBoxItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'packingBoxItems';

    protected static ?string $title = 'Box Items';

    protected static ?string $recordTitleAttribute = 'product_name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('shipment_item_id')
                    ->label('Select Shipment Item')
                    ->options(function () {
                        $box = $this->getOwnerRecord();
                        $shipment = $box->shipment;
                        
                        return $shipment->items()
                            ->whereColumn('quantity_to_ship', '>', 'quantity_packed')
                            ->get()
                            ->mapWithKeys(function ($item) {
                                $available = $item->quantity_to_ship - $item->quantity_packed;
                                return [
                                    $item->id => sprintf(
                                        '%s - %s (Available: %d)',
                                        $item->product_sku,
                                        $item->product_name,
                                        $available
                                    )
                                ];
                            });
                    })
                    ->required()
                    ->searchable()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $item = ShipmentItem::find($state);
                            if ($item) {
                                $set('product_id', $item->product_id);
                                $set('unit_weight', $item->unit_weight);
                                $set('unit_volume', $item->unit_volume);
                                $set('quantity_available', $item->quantity_to_ship - $item->quantity_packed);
                            }
                        }
                    }),

                Placeholder::make('quantity_available')
                    ->label('Quantity Available')
                    ->content(fn ($get) => ($get('quantity_available') ?? 0) . ' units'),

                TextInput::make('quantity')
                    ->label('Quantity to Pack')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, $get) {
                        $unitWeight = $get('unit_weight') ?? 0;
                        $unitVolume = $get('unit_volume') ?? 0;
                        $set('total_weight', $state * $unitWeight);
                        $set('total_volume', $state * $unitVolume);
                    }),

                Placeholder::make('total_weight')
                    ->label('Total Weight')
                    ->content(fn ($get) => number_format($get('total_weight') ?? 0, 2) . ' kg'),

                Placeholder::make('total_volume')
                    ->label('Total Volume')
                    ->content(fn ($get) => number_format($get('total_volume') ?? 0, 4) . ' m³'),

                TextInput::make('unit_weight')
                    ->hidden()
                    ->dehydrated(),

                TextInput::make('unit_volume')
                    ->hidden()
                    ->dehydrated(),

                TextInput::make('product_id')
                    ->hidden()
                    ->dehydrated(),
            ]);
    }

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
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('unit_weight')
                    ->label('Unit Weight')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' kg')
                    ->alignCenter(),

                TextColumn::make('unit_volume')
                    ->label('Unit Volume')
                    ->numeric(decimalPlaces: 4)
                    ->suffix(' m³')
                    ->alignCenter(),

                TextColumn::make('total_weight')
                    ->label('Total Weight')
                    ->state(fn ($record) => $record->quantity * $record->unit_weight)
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' kg')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('total_volume')
                    ->label('Total Volume')
                    ->state(fn ($record) => $record->quantity * $record->unit_volume)
                    ->numeric(decimalPlaces: 4)
                    ->suffix(' m³')
                    ->alignCenter()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->modalWidth('2xl')
                    ->after(function ($record) {
                        $shipmentItem = $record->shipmentItem;
                        if ($shipmentItem) {
                            $shipmentItem->updatePackedQuantity();
                        }
                        $this->getOwnerRecord()->recalculateTotals();
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->modalWidth('2xl')
                    ->after(function ($record) {
                        $shipmentItem = $record->shipmentItem;
                        if ($shipmentItem) {
                            $shipmentItem->updatePackedQuantity();
                        }
                        $this->getOwnerRecord()->recalculateTotals();
                    }),
                DeleteAction::make()
                    ->after(function ($record) {
                        $shipmentItem = $record->shipmentItem;
                        if ($shipmentItem) {
                            $shipmentItem->updatePackedQuantity();
                        }
                        $this->getOwnerRecord()->recalculateTotals();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->after(function ($records) {
                            foreach ($records as $record) {
                                $shipmentItem = $record->shipmentItem;
                                if ($shipmentItem) {
                                    $shipmentItem->updatePackedQuantity();
                                }
                            }
                            $this->getOwnerRecord()->recalculateTotals();
                        }),
                ]),
            ])
            ->emptyStateHeading('No items in this box yet')
            ->emptyStateDescription('Add items from the shipment to pack in this box');
    }
}
