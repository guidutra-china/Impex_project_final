<?php

namespace App\Filament\Resources\Shipments\RelationManagers;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;
use Filament\Support\Icons\Heroicon;
use BackedEnum;

class ContainerItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Container Items';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?string $recordTitleAttribute = 'product_name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Select Item')
                    ->description('Choose a shipment item to add to this container')
                    ->schema([
                        Select::make('shipment_item_id')
                            ->label('Shipment Item')
                            ->options(function ($livewire, $record) {
                                $container = $livewire->getOwnerRecord();
                                $shipment = $container->shipment;
                                
                                // Get items that are not fully packed yet
                                $availableItems = $shipment->items()
                                    ->whereColumn('quantity_packed', '<', 'quantity_to_ship')
                                    ->get();
                                
                                $options = [];
                                foreach ($availableItems as $item) {
                                    $remaining = $item->quantity_to_ship - $item->quantity_packed;
                                    $options[$item->id] = sprintf(
                                        '%s - %s (Available: %d units)',
                                        $item->product_sku,
                                        $item->product_name,
                                        $remaining
                                    );
                                }
                                
                                // When editing, include the current shipment item even if fully packed
                                if ($record && $record->shipmentItem) {
                                    $currentItem = $record->shipmentItem;
                                    if (!isset($options[$currentItem->id])) {
                                        $options[$currentItem->id] = sprintf(
                                            '%s - %s (Current item)',
                                            $currentItem->product_sku,
                                            $currentItem->product_name
                                        );
                                    }
                                }
                                
                                return $options;
                            })
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    $shipmentItem = \App\Models\ShipmentItem::find($state);
                                    if ($shipmentItem) {
                                        $set('product_id', $shipmentItem->product_id);
                                        $set('proforma_invoice_item_id', $shipmentItem->proforma_invoice_item_id);
                                        $set('unit_weight', $shipmentItem->unit_weight);
                                        $set('unit_volume', $shipmentItem->unit_volume);
                                        $set('unit_price', $shipmentItem->unit_price);
                                        $set('hs_code', $shipmentItem->hs_code);
                                        $set('country_of_origin', $shipmentItem->country_of_origin);
                                        $set('quantity_available', $shipmentItem->quantity_to_ship - $shipmentItem->quantity_packed);
                                    }
                                }
                            })
                            ->disabled(fn ($record) => $record !== null)
                            ->columnSpanFull(),

                        TextInput::make('quantity_available')
                            ->label('Quantity Available')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->suffix('units')
                            ->columnSpanFull(),

                        TextInput::make('quantity')
                            ->label('Quantity to Pack')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->suffix('units')
                            ->reactive()
                            ->afterStateUpdated(function ($state, $get, $set) {
                                $unitWeight = $get('unit_weight') ?? 0;
                                $unitVolume = $get('unit_volume') ?? 0;
                                $unitPrice = $get('unit_price') ?? 0;
                                
                                $set('total_weight', $state * $unitWeight);
                                $set('total_volume', $state * $unitVolume);
                                $set('customs_value', $state * $unitPrice);
                            })
                            ->columnSpanFull(),
                    ]),

                Section::make('Calculated Values')
                    ->description('Auto-calculated based on quantity')
                    ->schema([
                        TextInput::make('total_weight')
                            ->label('Total Weight')
                            ->numeric()
                            ->disabled()
                            ->suffix('kg')
                            ->columnSpanFull(),

                        TextInput::make('total_volume')
                            ->label('Total Volume')
                            ->numeric()
                            ->disabled()
                            ->suffix('m³')
                            ->columnSpanFull(),

                        TextInput::make('customs_value')
                            ->label('Customs Value')
                            ->numeric()
                            ->disabled()
                            ->prefix('$')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Additional Information')
                    ->schema([
                        Textarea::make('notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->limit(30),

                TextColumn::make('quantity')
                    ->label('Qty')
                    ->numeric()
                    ->alignCenter()
                    ->weight('bold'),

                TextColumn::make('total_weight')
                    ->label('Weight')
                    ->numeric(2)
                    ->suffix(' kg')
                    ->alignEnd(),

                TextColumn::make('total_volume')
                    ->label('Volume')
                    ->numeric(4)
                    ->suffix(' m³')
                    ->alignEnd(),

                BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'draft',
                        'success' => 'packed',
                        'primary' => 'sealed',
                    ]),

                TextColumn::make('customs_value')
                    ->label('Value')
                    ->money('USD')
                    ->toggleable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->icon(Heroicon::OutlinedPlus)
                    ->modalWidth('2xl')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Load data from shipment item
                        $shipmentItem = \App\Models\ShipmentItem::find($data['shipment_item_id']);
                        
                        if ($shipmentItem) {
                            $data['product_id'] = $shipmentItem->product_id;
                            $data['proforma_invoice_item_id'] = $shipmentItem->proforma_invoice_item_id;
                            $data['unit_weight'] = $shipmentItem->unit_weight;
                            $data['unit_volume'] = $shipmentItem->unit_volume;
                            $data['unit_price'] = $shipmentItem->unit_price;
                            $data['hs_code'] = $shipmentItem->hs_code;
                            $data['country_of_origin'] = $shipmentItem->country_of_origin;
                            $data['status'] = 'draft';
                            $data['shipment_sequence'] = 1;
                            
                            // Calculate totals
                            $quantity = $data['quantity'] ?? 0;
                            $data['total_weight'] = $quantity * $data['unit_weight'];
                            $data['total_volume'] = $quantity * $data['unit_volume'];
                            $data['customs_value'] = $quantity * $data['unit_price'];
                        }
                        
                        // Remove temporary field
                        unset($data['shipment_item_id']);
                        unset($data['quantity_available']);
                        
                        return $data;
                    })
                    ->after(function ($record, $livewire) {
                        // Update container totals
                        $container = $livewire->getOwnerRecord();
                        $container->recalculateTotals();
                        
                        // Update shipment item packed quantity
                        $shipmentItem = \App\Models\ShipmentItem::where('product_id', $record->product_id)
                            ->where('shipment_id', $container->shipment_id)
                            ->first();
                        
                        if ($shipmentItem) {
                            $shipmentItem->updatePackedQuantity();
                        }
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->modalWidth('2xl'),
                DeleteAction::make()
                    ->after(function ($record, $livewire) {
                        // Update container totals
                        $container = $livewire->getOwnerRecord();
                        $container->recalculateTotals();
                        
                        // Update shipment item packed quantity
                        $shipmentItem = \App\Models\ShipmentItem::where('product_id', $record->product_id)
                            ->where('shipment_id', $container->shipment_id)
                            ->first();
                        
                        if ($shipmentItem) {
                            $shipmentItem->updatePackedQuantity();
                        }
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No items in this container')
            ->emptyStateDescription('Add shipment items to this container to start packing.')
            ->emptyStateIcon(Heroicon::OutlinedCube);
    }
}
