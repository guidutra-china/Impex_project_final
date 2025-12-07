<?php

namespace App\Filament\Resources\Shipments\RelationManagers;

use App\Services\Shipment\ShipmentService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Collection;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;
use BackedEnum;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Shipment Items';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?string $recordTitleAttribute = 'product_name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Item Selection')
                    ->schema([
                        Select::make('proforma_invoice_item_id')
                            ->label('Select Item from Proforma Invoice')
                            ->options(function ($livewire) {
                                $shipment = $livewire->getOwnerRecord();
                                $service = new ShipmentService();
                                $availableItems = $service->getAvailableItems($shipment);
                                
                                $options = [];
                                foreach ($availableItems as $item) {
                                    $options[$item['item_id']] = sprintf(
                                        '[%s] %s - %s',
                                        $item['invoice_number'],
                                        $item['product_sku'],
                                        $item['product_name']
                                    );
                                }
                                
                                return $options;
                            })
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    $invoiceItem = \App\Models\ProformaInvoiceItem::find($state);
                                    if ($invoiceItem) {
                                        $set('product_id', $invoiceItem->product_id);
                                        $set('product_name', $invoiceItem->product_name);
                                        $set('product_sku', $invoiceItem->product_sku);
                                        $set('quantity_ordered', $invoiceItem->quantity);
                                        $set('quantity_available', $invoiceItem->getQuantityRemaining());
                                        $set('unit_price', $invoiceItem->unit_price);
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
                            ->helperText('Remaining quantity from this proforma invoice item')
                            ->columnSpanFull(),

                        TextInput::make('quantity_to_ship')
                            ->label('Quantity to Ship')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->suffix('units')
                            ->helperText('Enter the quantity you want to ship')
                            ->columnSpanFull(),
                    ]),

                Section::make('Product Information')
                    ->description('Auto-filled from selected item')
                    ->schema([
                        TextInput::make('product_sku')
                            ->label('Product SKU')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),

                        TextInput::make('product_name')
                            ->label('Product Name')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),

                        TextInput::make('unit_price')
                            ->label('Unit Price')
                            ->numeric()
                            ->prefix('$')
                            ->disabled()
                            ->dehydrated(false)
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
                TextColumn::make('proformaInvoiceItem.proformaInvoice.proforma_number')
                    ->label('Proforma #')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('product_sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('product_name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                TextColumn::make('hs_code')
                    ->label('HS Code')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('quantity_ordered')
                    ->label('Ordered')
                    ->numeric()
                    ->alignCenter(),

                \Filament\Tables\Columns\TextInputColumn::make('quantity_to_ship')
                    ->label('To Ship')
                    ->type('number')
                    ->rules(['required', 'numeric', 'min:1'])
                    ->alignCenter()
                    ->disabled(fn ($record) => $record->packing_status !== 'unpacked')
                    ->afterStateUpdated(function ($record, $state) {
                        $record->quantity_remaining = $state - $record->quantity_packed;
                        $record->save();
                    })
                    ->extraAttributes(['class' => 'font-bold']),

                TextColumn::make('quantity_shipped')
                    ->label('Shipped')
                    ->numeric()
                    ->alignCenter(),

                TextColumn::make('quantity_remaining')
                    ->label('Remaining')
                    ->numeric()
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'success'),

                BadgeColumn::make('packing_status')
                    ->label('Packing')
                    ->colors([
                        'danger' => 'unpacked',
                        'warning' => 'partially_packed',
                        'success' => 'fully_packed',
                    ])
                    ->icons([
                        'heroicon-o-x-circle' => 'unpacked',
                        'heroicon-o-clock' => 'partially_packed',
                        'heroicon-o-check-circle' => 'fully_packed',
                    ]),

                TextColumn::make('unit_price')
                    ->label('Unit Price')
                    ->money('USD')
                    ->toggleable(),

                TextColumn::make('customs_value')
                    ->label('Customs Value')
                    ->money('USD')
                    ->toggleable(),

                TextColumn::make('total_weight')
                    ->label('Weight (kg)')
                    ->numeric(2)
                    ->suffix(' kg')
                    ->toggleable(),

                TextColumn::make('total_volume')
                    ->label('Volume (m³)')
                    ->numeric(4)
                    ->suffix(' m³')
                    ->toggleable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->icon(Heroicon::OutlinedPlus)
                    ->modalWidth('3xl')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Load product data from proforma invoice item
                        $invoiceItem = \App\Models\ProformaInvoiceItem::find($data['proforma_invoice_item_id']);
                        
                        if ($invoiceItem) {
                            $data['product_id'] = $invoiceItem->product_id;
                            $data['product_name'] = $invoiceItem->product_name;
                            $data['product_sku'] = $invoiceItem->product_sku;
                            $data['product_description'] = $invoiceItem->product_description;
                            $data['hs_code'] = $invoiceItem->hs_code;
                            $data['unit_price'] = $invoiceItem->unit_price;
                            $data['quantity_ordered'] = $invoiceItem->quantity;
                            $data['quantity_remaining'] = $data['quantity_to_ship'];
                            $data['packing_status'] = 'unpacked';
                            $data['quantity_packed'] = 0;
                            
                            // Load product weight and volume if available
                            if ($invoiceItem->product) {
                                $data['unit_weight'] = $invoiceItem->product->weight;
                                $data['unit_volume'] = $invoiceItem->product->volume;
                                $data['country_of_origin'] = $invoiceItem->product->country_of_origin;
                            }
                        }
                        
                        return $data;
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->modalWidth('3xl'),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('packSelectedItems')
                        ->label('Pack Selected Items')
                        ->icon(Heroicon::OutlinedCube)
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Pack Selected Items')
                        ->modalWidth('2xl')
                        ->form(function ($livewire) {
                            $shipment = $livewire->getOwnerRecord();
                            return [
                                Radio::make('destination_type')
                                    ->label('Pack into')
                                    ->options(['container' => 'Container', 'box' => 'Packing Box/Pallet'])
                                    ->required()->reactive()->default('container'),
                                Select::make('container_id')
                                    ->label('Select Container')
                                    ->options(fn () => $shipment->containers()->where('status', '!=', 'sealed')->pluck('container_number', 'id'))
                                    ->searchable()
                                    ->visible(fn ($get) => $get('destination_type') === 'container')
                                    ->required(fn ($get) => $get('destination_type') === 'container')
                                    ->createOptionForm([
                                        Select::make('container_type_id')
                                            ->label('Container Type')
                                            ->options(\App\Models\ContainerType::pluck('name', 'id'))
                                            ->required()
                                            ->searchable()
                                            ->preload(),
                                        TextInput::make('container_number')
                                            ->label('Container Number')
                                            ->required()
                                            ->maxLength(50),
                                        TextInput::make('seal_number')
                                            ->label('Seal Number')
                                            ->maxLength(50),
                                    ])
                                    ->createOptionUsing(function (array $data) use ($shipment) {
                                        $containerType = \App\Models\ContainerType::find($data['container_type_id']);
                                        
                                        $container = \App\Models\ShipmentContainer::create([
                                            'shipment_id' => $shipment->id,
                                            'container_type_id' => $data['container_type_id'],
                                            'container_number' => $data['container_number'],
                                            'seal_number' => $data['seal_number'] ?? null,
                                            'status' => 'draft',
                                            'max_weight' => $containerType->max_weight ?? 0,
                                            'max_volume' => $containerType->max_volume ?? 0,
                                            'created_by' => auth()->id(),
                                        ]);
                                        return $container->id;
                                    })
                                    ->helperText('Click + to create a new container'),
                                Select::make('box_id')
                                    ->label('Select Box/Pallet')
                                    ->options(fn () => $shipment->packingBoxes()->where('packing_status', '!=', 'sealed')->get()->mapWithKeys(fn ($b) => [$b->id => sprintf('%s - %s', $b->box_label ?? "Box #{$b->box_number}", ucfirst($b->box_type))]))
                                    ->searchable()
                                    ->visible(fn ($get) => $get('destination_type') === 'box')
                                    ->required(fn ($get) => $get('destination_type') === 'box')
                                    ->createOptionForm([
                                        Select::make('box_type')
                                            ->label('Box Type')
                                            ->options([
                                                'box' => 'Box',
                                                'pallet' => 'Pallet',
                                                'crate' => 'Crate',
                                                'bundle' => 'Bundle',
                                            ])
                                            ->required()
                                            ->default('box'),
                                        TextInput::make('box_label')
                                            ->label('Box Label (optional)')
                                            ->maxLength(100)
                                            ->helperText('e.g., "Electronics Box 1", "Fragile Items"'),
                                        TextInput::make('length')
                                            ->label('Length (cm)')
                                            ->numeric()
                                            ->default(0),
                                        TextInput::make('width')
                                            ->label('Width (cm)')
                                            ->numeric()
                                            ->default(0),
                                        TextInput::make('height')
                                            ->label('Height (cm)')
                                            ->numeric()
                                            ->default(0),
                                    ])
                                    ->createOptionUsing(function (array $data) use ($shipment) {
                                        $box = \App\Models\PackingBox::create([
                                            'shipment_id' => $shipment->id,
                                            'box_type' => $data['box_type'],
                                            'box_label' => $data['box_label'] ?? null,
                                            'length' => $data['length'] ?? 0,
                                            'width' => $data['width'] ?? 0,
                                            'height' => $data['height'] ?? 0,
                                            'packing_status' => 'empty',
                                        ]);
                                        return $box->id;
                                    })
                                    ->helperText('Click + to create a new box/pallet'),
                                
                                // Capacity validation info
                                \Filament\Forms\Components\Placeholder::make('capacity_info')
                                    ->label('Capacity Check')
                                    ->content(function ($get, $livewire) use ($shipment) {
                                        $destinationType = $get('destination_type');
                                        $containerId = $get('container_id');
                                        $boxId = $get('box_id');
                                        
                                        if (!$destinationType) {
                                            return 'Select a destination to see capacity information.';
                                        }
                                        
                                        // Get selected items
                                        $selectedRecords = $livewire->getSelectedTableRecords();
                                        if ($selectedRecords->isEmpty()) {
                                            return 'No items selected.';
                                        }
                                        
                                        // Calculate total weight and volume of selected items
                                        $totalWeight = 0;
                                        $totalVolume = 0;
                                        
                                        foreach ($selectedRecords as $item) {
                                            $qty = $item->quantity_to_ship - $item->quantity_packed;
                                            if ($qty > 0) {
                                                $product = $item->product;
                                                
                                                // Calculate weight and volume
                                                if ($product && $product->pcs_per_carton > 0) {
                                                    $unitWeight = $product->carton_weight / $product->pcs_per_carton;
                                                    $unitVolume = $product->carton_cbm / $product->pcs_per_carton;
                                                } else {
                                                    $unitWeight = $product->net_weight ?? 0;
                                                    if ($product && $product->product_length && $product->product_width && $product->product_height) {
                                                        $unitVolume = ($product->product_length * $product->product_width * $product->product_height) / 1000000;
                                                    } else {
                                                        $unitVolume = 0;
                                                    }
                                                }
                                                
                                                $totalWeight += $qty * $unitWeight;
                                                $totalVolume += $qty * $unitVolume;
                                            }
                                        }
                                        
                                        // Get destination capacity
                                        if ($destinationType === 'container' && $containerId) {
                                            $container = \App\Models\ShipmentContainer::find($containerId);
                                            if ($container) {
                                                $currentWeight = $container->current_weight ?? 0;
                                                $currentVolume = $container->current_volume ?? 0;
                                                $maxWeight = $container->max_weight ?? 0;
                                                $maxVolume = $container->max_volume ?? 0;
                                                
                                                $afterWeight = $currentWeight + $totalWeight;
                                                $afterVolume = $currentVolume + $totalVolume;
                                                
                                                $weightPercent = $maxWeight > 0 ? ($afterWeight / $maxWeight * 100) : 0;
                                                $volumePercent = $maxVolume > 0 ? ($afterVolume / $maxVolume * 100) : 0;
                                                
                                                $weightStatus = $afterWeight > $maxWeight ? '⚠️ EXCEEDS' : ($weightPercent > 90 ? '⚠️ NEAR LIMIT' : '✅ OK');
                                                $volumeStatus = $afterVolume > $maxVolume ? '⚠️ EXCEEDS' : ($volumePercent > 90 ? '⚠️ NEAR LIMIT' : '✅ OK');
                                                
                                                return sprintf(
                                                    "**Container: %s**\n\n" .
                                                    "**Weight:** %s\n" .
                                                    "Current: %.2f kg\n" .
                                                    "Selected Items: %.2f kg\n" .
                                                    "After Packing: %.2f / %.2f kg (%.1f%%)\n\n" .
                                                    "**Volume:** %s\n" .
                                                    "Current: %.3f m³\n" .
                                                    "Selected Items: %.3f m³\n" .
                                                    "After Packing: %.3f / %.3f m³ (%.1f%%)",
                                                    $container->container_number,
                                                    $weightStatus,
                                                    $currentWeight,
                                                    $totalWeight,
                                                    $afterWeight,
                                                    $maxWeight,
                                                    $weightPercent,
                                                    $volumeStatus,
                                                    $currentVolume,
                                                    $totalVolume,
                                                    $afterVolume,
                                                    $maxVolume,
                                                    $volumePercent
                                                );
                                            }
                                        } elseif ($destinationType === 'box' && $boxId) {
                                            $box = \App\Models\PackingBox::find($boxId);
                                            if ($box) {
                                                $currentWeight = $box->total_weight ?? 0;
                                                $currentVolume = $box->total_volume ?? 0;
                                                $maxVolume = $box->volume ?? 0;
                                                
                                                $afterWeight = $currentWeight + $totalWeight;
                                                $afterVolume = $currentVolume + $totalVolume;
                                                
                                                $volumePercent = $maxVolume > 0 ? ($afterVolume / $maxVolume * 100) : 0;
                                                $volumeStatus = $afterVolume > $maxVolume ? '⚠️ EXCEEDS' : ($volumePercent > 90 ? '⚠️ NEAR LIMIT' : '✅ OK');
                                                
                                                return sprintf(
                                                    "**Box: %s**\n\n" .
                                                    "**Weight:**\n" .
                                                    "Current: %.2f kg\n" .
                                                    "Selected Items: %.2f kg\n" .
                                                    "After Packing: %.2f kg\n\n" .
                                                    "**Volume:** %s\n" .
                                                    "Current: %.3f m³\n" .
                                                    "Selected Items: %.3f m³\n" .
                                                    "After Packing: %.3f / %.3f m³ (%.1f%%)",
                                                    $box->box_label ?? "Box #{$box->box_number}",
                                                    $currentWeight,
                                                    $totalWeight,
                                                    $afterWeight,
                                                    $volumeStatus,
                                                    $currentVolume,
                                                    $totalVolume,
                                                    $afterVolume,
                                                    $maxVolume,
                                                    $volumePercent
                                                );
                                            }
                                        }
                                        
                                        return sprintf(
                                            "**Selected Items:**\n" .
                                            "Total Weight: %.2f kg\n" .
                                            "Total Volume: %.3f m³\n\n" .
                                            "Select a %s to see capacity check.",
                                            $totalWeight,
                                            $totalVolume,
                                            $destinationType === 'container' ? 'container' : 'box'
                                        );
                                    })
                                    ->columnSpanFull(),
                            ];
                        })
                        ->action(function (Collection $records, array $data) {
                            if ($data['destination_type'] === 'container') {
                                $container = \App\Models\ShipmentContainer::find($data['container_id']);
                                foreach ($records as $item) {
                                    $qty = $item->quantity_to_ship - $item->quantity_packed;
                                    if ($qty > 0) {
                                        // Calculate weight and volume from product packaging info
                                        $product = $item->product;
                                        
                                        // Calculate based on master carton (standard shipping unit)
                                        if ($product && $product->pcs_per_carton > 0) {
                                            // Calculate number of cartons needed
                                            $cartons = ceil($qty / $product->pcs_per_carton);
                                            
                                            // Weight per piece (from carton)
                                            $unitWeight = $product->carton_weight / $product->pcs_per_carton;
                                            
                                            // Volume per piece (from carton CBM)
                                            $unitVolume = $product->carton_cbm / $product->pcs_per_carton;
                                        } else {
                                            // Fallback to product net weight and calculated volume
                                            $unitWeight = $product->net_weight ?? 0;
                                            
                                            // Calculate volume from product dimensions (L x W x H in cm to m³)
                                            if ($product && $product->product_length && $product->product_width && $product->product_height) {
                                                $unitVolume = ($product->product_length * $product->product_width * $product->product_height) / 1000000;
                                            } else {
                                                $unitVolume = 0;
                                            }
                                        }
                                        
                                        \App\Models\ShipmentContainerItem::create([
                                            'shipment_container_id' => $container->id,
                                            'proforma_invoice_item_id' => $item->proforma_invoice_item_id,
                                            'product_id' => $item->product_id,
                                            'quantity' => $qty,
                                            'unit_weight' => $unitWeight,
                                            'total_weight' => $qty * $unitWeight,
                                            'unit_volume' => $unitVolume,
                                            'total_volume' => $qty * $unitVolume,
                                            'unit_price' => $item->unit_price,
                                            'customs_value' => $qty * $item->unit_price,
                                            'hs_code' => $item->hs_code,
                                            'country_of_origin' => $item->country_of_origin,
                                            'status' => 'draft',
                                            'shipment_sequence' => 1,
                                        ]);
                                        $item->updatePackedQuantity();
                                    }
                                }
                                $container->recalculateTotals();
                            } else {
                                $box = \App\Models\PackingBox::find($data['box_id']);
                                foreach ($records as $item) {
                                    $qty = $item->quantity_to_ship - $item->quantity_packed;
                                    if ($qty > 0) {
                                        // Calculate weight and volume from product packaging info
                                        $product = $item->product;
                                        
                                        // Calculate based on master carton (standard shipping unit)
                                        if ($product && $product->pcs_per_carton > 0) {
                                            // Weight per piece (from carton)
                                            $unitWeight = $product->carton_weight / $product->pcs_per_carton;
                                            
                                            // Volume per piece (from carton CBM)
                                            $unitVolume = $product->carton_cbm / $product->pcs_per_carton;
                                        } else {
                                            // Fallback to product net weight and calculated volume
                                            $unitWeight = $product->net_weight ?? 0;
                                            
                                            // Calculate volume from product dimensions (L x W x H in cm to m³)
                                            if ($product && $product->product_length && $product->product_width && $product->product_height) {
                                                $unitVolume = ($product->product_length * $product->product_width * $product->product_height) / 1000000;
                                            } else {
                                                $unitVolume = 0;
                                            }
                                        }
                                        
                                        \App\Models\PackingBoxItem::create([
                                            'packing_box_id' => $box->id,
                                            'shipment_item_id' => $item->id,
                                            'product_id' => $item->product_id,
                                            'quantity' => $qty,
                                            'unit_weight' => $unitWeight,
                                            'unit_volume' => $unitVolume,
                                        ]);
                                        $item->updatePackedQuantity();
                                    }
                                }
                                $box->recalculateTotals();
                            }
                            \Filament\Notifications\Notification::make()->title('Items packed successfully')->success()->send();
                        }),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No items added yet')
            ->emptyStateDescription('Add items from the attached proforma invoices to this shipment.')
            ->emptyStateIcon(Heroicon::OutlinedCube);
    }
}
