<?php

namespace App\Filament\Resources\Shipments\RelationManagers;

use App\Services\Shipment\ShipmentService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\BulkAction;
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
                                    ->options([
                                        'container' => 'Container',
                                        'box' => 'Packing Box/Pallet',
                                    ])
                                    ->required()
                                    ->reactive()
                                    ->default('container'),
                                
                                Select::make('container_id')
                                    ->label('Select Container')
                                    ->options(function () use ($shipment) {
                                        return $shipment->containers()
                                            ->where('status', '!=', 'sealed')
                                            ->pluck('container_number', 'id');
                                    })
                                    ->searchable()
                                    ->visible(fn ($get) => $get('destination_type') === 'container')
                                    ->required(fn ($get) => $get('destination_type') === 'container')
                                    ->helperText('Create new containers in the Containers tab'),
                                
                                Select::make('box_id')
                                    ->label('Select Box/Pallet')
                                    ->options(function () use ($shipment) {
                                        return $shipment->packingBoxes()
                                            ->where('packing_status', '!=', 'sealed')
                                            ->get()
                                            ->mapWithKeys(fn ($b) => [
                                                $b->id => sprintf('%s - %s', $b->box_label ?? "Box #{$b->box_number}", ucfirst($b->box_type))
                                            ]);
                                    })
                                    ->searchable()
                                    ->visible(fn ($get) => $get('destination_type') === 'box')
                                    ->required(fn ($get) => $get('destination_type') === 'box')
                                    ->helperText('Create new boxes in the Packing Boxes tab'),
                            ];
                        })
                        ->action(function (Collection $records, array $data, $livewire) {
                            $destinationType = $data['destination_type'];
                            
                            if ($destinationType === 'container') {
                                $container = \App\Models\ShipmentContainer::find($data['container_id']);
                                
                                foreach ($records as $item) {
                                    $quantityToPack = $item->quantity_to_ship - $item->quantity_packed;
                                    
                                    if ($quantityToPack > 0) {
                                        \App\Models\ShipmentContainerItem::create([
                                            'shipment_container_id' => $container->id,
                                            'proforma_invoice_item_id' => $item->proforma_invoice_item_id,
                                            'product_id' => $item->product_id,
                                            'quantity' => $quantityToPack,
                                            'unit_weight' => $item->unit_weight,
                                            'total_weight' => $quantityToPack * $item->unit_weight,
                                            'unit_volume' => $item->unit_volume,
                                            'total_volume' => $quantityToPack * $item->unit_volume,
                                            'unit_price' => $item->unit_price,
                                            'customs_value' => $quantityToPack * $item->unit_price,
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
                                    $quantityToPack = $item->quantity_to_ship - $item->quantity_packed;
                                    
                                    if ($quantityToPack > 0) {
                                        \App\Models\PackingBoxItem::create([
                                            'packing_box_id' => $box->id,
                                            'shipment_item_id' => $item->id,
                                            'product_id' => $item->product_id,
                                            'quantity' => $quantityToPack,
                                            'unit_weight' => $item->unit_weight,
                                            'unit_volume' => $item->unit_volume,
                                        ]);
                                        
                                        $item->updatePackedQuantity();
                                    }
                                }
                                
                                $box->recalculateTotals();
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Items packed successfully')
                                ->success()
                                ->send();
                        }),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No items added yet')
            ->emptyStateDescription('Add items from the attached proforma invoices to this shipment.')
            ->emptyStateIcon(Heroicon::OutlinedCube);
    }
}
