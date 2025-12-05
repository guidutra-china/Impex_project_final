<?php

namespace App\Filament\Resources\Shipments\RelationManagers;

use App\Services\Shipment\ShipmentService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
                        Select::make('sales_invoice_item_id')
                            ->label('Invoice Item')
                            ->options(function ($livewire) {
                                $shipment = $livewire->getOwnerRecord();
                                $service = new ShipmentService();
                                $availableItems = $service->getAvailableItems($shipment);
                                
                                $options = [];
                                foreach ($availableItems as $item) {
                                    $options[$item['item_id']] = sprintf(
                                        '[%s] %s - %s (Remaining: %d)',
                                        $item['invoice_number'],
                                        $item['product_sku'],
                                        $item['product_name'],
                                        $item['quantity_remaining']
                                    );
                                }
                                
                                return $options;
                            })
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    $invoiceItem = \App\Models\SalesInvoiceItem::find($state);
                                    if ($invoiceItem) {
                                        $set('product_id', $invoiceItem->product_id);
                                        $set('product_name', $invoiceItem->product_name);
                                        $set('product_sku', $invoiceItem->product_sku);
                                        $set('quantity_ordered', $invoiceItem->quantity);
                                        $set('unit_price', $invoiceItem->unit_price);
                                    }
                                }
                            })
                            ->disabled(fn ($record) => $record !== null),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('quantity_to_ship')
                                    ->label('Quantity to Ship')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->helperText(function ($get) {
                                        $invoiceItemId = $get('sales_invoice_item_id');
                                        if ($invoiceItemId) {
                                            $invoiceItem = \App\Models\SalesInvoiceItem::find($invoiceItemId);
                                            if ($invoiceItem) {
                                                return "Available: {$invoiceItem->quantity_remaining}";
                                            }
                                        }
                                        return '';
                                    }),

                                TextInput::make('quantity_ordered')
                                    ->label('Quantity Ordered')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                    ]),

                Section::make('Product Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('product_name')
                                    ->label('Product Name')
                                    ->disabled()
                                    ->dehydrated(false),

                                TextInput::make('product_sku')
                                    ->label('SKU')
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),

                        TextInput::make('unit_price')
                            ->label('Unit Price')
                            ->numeric()
                            ->prefix('$')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('salesInvoiceItem.salesInvoice.invoice_number')
                    ->label('Invoice #')
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
                    ->alignCenter()
                    ->toggleable(),

                TextColumn::make('quantity_to_ship')
                    ->label('To Ship')
                    ->alignCenter()
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('quantity_shipped')
                    ->label('Shipped')
                    ->alignCenter()
                    ->toggleable(),

                BadgeColumn::make('packing_status')
                    ->label('Packing')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'unpacked' => 'Unpacked',
                        'partially_packed' => 'Partial',
                        'fully_packed' => 'Packed',
                        default => 'N/A',
                    })
                    ->colors([
                        'secondary' => 'unpacked',
                        'warning' => 'partially_packed',
                        'success' => 'fully_packed',
                    ]),

                TextColumn::make('quantity_packed')
                    ->label('Packed')
                    ->alignCenter()
                    ->default(0),

                TextColumn::make('quantity_remaining')
                    ->label('Remaining')
                    ->alignCenter()
                    ->default(0)
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'success'),

                TextColumn::make('unit_weight')
                    ->label('Unit Wt (kg)')
                    ->numeric(3)
                    ->alignEnd()
                    ->toggleable(),

                TextColumn::make('total_weight')
                    ->label('Total Wt (kg)')
                    ->numeric(3)
                    ->alignEnd()
                    ->toggleable(),

                TextColumn::make('unit_volume')
                    ->label('Unit Vol (m³)')
                    ->numeric(6)
                    ->alignEnd()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                TextColumn::make('total_volume')
                    ->label('Total Vol (m³)')
                    ->numeric(6)
                    ->alignEnd()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                TextColumn::make('customs_value')
                    ->label('Customs Value')
                    ->money('USD', 100)
                    ->alignEnd()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add Item')
                    ->color('success')
                    ->icon(Heroicon::OutlinedPlus)
                    ->using(function (array $data, $livewire) {
                        $shipment = $livewire->getOwnerRecord();
                        $service = new ShipmentService();
                        
                        $invoiceItem = \App\Models\SalesInvoiceItem::findOrFail($data['sales_invoice_item_id']);
                        
                        return $service->addItem($shipment, [
                            'sales_invoice_item_id' => $data['sales_invoice_item_id'],
                            'product_id' => $invoiceItem->product_id,
                            'quantity_ordered' => $invoiceItem->quantity,
                            'quantity_to_ship' => $data['quantity_to_ship'],
                            'product_name' => $invoiceItem->product_name,
                            'product_sku' => $invoiceItem->product_sku,
                            'unit_price' => $invoiceItem->unit_price,
                        ]);
                    })
                    ->successNotificationTitle('Item added successfully'),
            ])
            ->recordActions([
                EditAction::make()
                    ->form([
                        TextInput::make('quantity_to_ship')
                            ->label('Quantity to Ship')
                            ->numeric()
                            ->required()
                            ->minValue(1),
                    ])
                    ->using(function ($record, array $data) {
                        $service = new ShipmentService();
                        $service->updateItemQuantity($record, $data['quantity_to_ship']);
                        return $record;
                    })
                    ->successNotificationTitle('Quantity updated'),

                DeleteAction::make()
                    ->requiresConfirmation()
                    ->using(function ($record) {
                        $service = new ShipmentService();
                        $service->removeItem($record);
                    })
                    ->successNotificationTitle('Item removed'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->emptyStateHeading('No items added')
            ->emptyStateDescription('Add items from attached invoices to this shipment.')
            ->emptyStateIcon(Heroicon::OutlinedCube);
    }
}
