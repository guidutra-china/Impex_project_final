<?php

namespace App\Filament\Resources\ProformaInvoice\RelationManagers;

use App\Models\QuoteItem;
use App\Models\SupplierQuote;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;
use Filament\Notifications\Notification;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Proforma Items';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Source Information')
                    ->description('Select the supplier quote and item to import')
                    ->schema([
                        Select::make('supplier_quote_id')
                            ->label('Source Supplier Quote')
                            ->options(function () {
                                return SupplierQuote::query()
                                    ->with('supplier')
                                    ->get()
                                    ->mapWithKeys(fn($quote) => [
                                        $quote->id => $quote->supplier->name . ' - ' . $quote->quote_number
                                    ]);
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                // Clear quote item when supplier quote changes
                                $set('quote_item_id', null);
                                $set('product_id', null);
                                $set('quantity', null);
                                $set('unit_price', null);
                                $set('commission_percent', null);
                                $set('commission_type', null);
                            })
                            ->helperText('Select supplier quote to load items from')
,

                        Select::make('quote_item_id')
                            ->label('Quote Item')
                            ->options(function (Get $get) {
                                $quoteId = $get('supplier_quote_id');
                                if (!$quoteId) {
                                    return [];
                                }

                                return QuoteItem::where('supplier_quote_id', $quoteId)
                                    ->with('product')
                                    ->get()
                                    ->mapWithKeys(function ($item) {
                                        $label = sprintf(
                                            '%s - Qty: %d @ $%s',
                                            $item->product?->name ?? 'Unknown',
                                            $item->quantity,
                                            number_format($item->unit_price_after_commission / 100, 2)
                                        );
                                        return [$item->id => $label];
                                    });
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                if (!$state) {
                                    return;
                                }

                                $quoteItem = QuoteItem::with('product')->find($state);
                                if (!$quoteItem) {
                                    return;
                                }

                                // Check for duplicate product across all proforma items
                                $proformaInvoice = $this->getOwnerRecord();
                                $existingItem = $proformaInvoice->items()
                                    ->where('product_id', $quoteItem->product_id)
                                    ->first();
                                
                                if ($existingItem) {
                                    $product = $quoteItem->product;
                                    
                                    Notification::make()
                                        ->danger()
                                        ->title('⚠️ ATTENTION: Product Already Exists!')
                                        ->body("**{$product->name}** (Code: {$product->code}) is already in this proforma invoice with quantity **{$existingItem->quantity}**.\n\n**You can either:**\n\n✅ **Cancel** and increase the quantity of the existing item\n\n⚠️ **Continue** to add as separate line ONLY if it has different specifications or comes from a different source\n\n**Please acknowledge by closing this notification.**")
                                        ->persistent()
                                        ->duration(null)
                                        ->send();
                                }

                                // Auto-fill from quote item
                                $set('product_id', $quoteItem->product_id);
                                $set('quantity', $quoteItem->quantity);
                                $set('unit_price', $quoteItem->unit_price_after_commission / 100); // Convert from cents
                                $set('commission_percent', $quoteItem->commission_percent);
                                $set('commission_type', $quoteItem->commission_type);
                                $set('delivery_days', $quoteItem->delivery_days);
                                
                                // Calculate total
                                $total = ($quoteItem->quantity * $quoteItem->unit_price_after_commission) / 100;
                                $set('total', $total);
                                
                                // Calculate commission amount
                                if ($quoteItem->commission_type === 'embedded') {
                                    $basePrice = $quoteItem->unit_price_before_commission / 100;
                                    $commissionAmount = (($quoteItem->unit_price_after_commission - $quoteItem->unit_price_before_commission) * $quoteItem->quantity) / 100;
                                    $set('commission_amount', $commissionAmount);
                                } else {
                                    $commissionAmount = ($quoteItem->unit_price_before_commission * $quoteItem->quantity * $quoteItem->commission_percent / 100) / 100;
                                    $set('commission_amount', $commissionAmount);
                                }
                            })
                            ->required()
                            ->disabled(fn (Get $get) => !$get('supplier_quote_id'))
                            ->helperText('Select item from the supplier quote')
,
                    ])
                    ->collapsible(),

                Section::make('Product & Pricing')
                    ->description('Product details and pricing information')
                    ->schema([
                        Select::make('product_id')
                            ->relationship('product', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('quantity')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $quantity = (float) $get('quantity');
                                $unitPrice = (float) $get('unit_price');
                                $total = $quantity * $unitPrice;
                                $set('total', $total);
                            })
,

                        TextInput::make('unit_price')
                            ->label('Unit Price (with commission)')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->prefix('$')
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $quantity = (float) $get('quantity');
                                $unitPrice = (float) $get('unit_price');
                                $total = $quantity * $unitPrice;
                                $set('total', $total);
                            })
,

                        TextInput::make('commission_percent')
                            ->label('Commission %')
                            ->numeric()
                            ->suffix('%')
                            ->disabled()
                            ->dehydrated()
,

                        Select::make('commission_type')
                            ->options([
                                'embedded' => 'Embedded',
                                'separate' => 'Separate',
                            ])
                            ->disabled()
                            ->dehydrated()
,

                        TextInput::make('commission_amount')
                            ->label('Commission Amount')
                            ->numeric()
                            ->prefix('$')
                            ->disabled()
                            ->dehydrated()
,

                        TextInput::make('total')
                            ->label('Total')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->prefix('$')
,

                        TextInput::make('delivery_days')
                            ->label('Delivery (days)')
                            ->numeric()
                            ->minValue(0)
,

                        Textarea::make('notes')
                            ->rows(2)
,
                    ])
                    ->collapsible(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product.name')
            ->modifyQueryUsing(fn ($query) => $query->with(['supplierQuote.supplier', 'product']))
            ->columns([
                TextColumn::make('product.code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->weight(FontWeight::Medium),

                TextColumn::make('supplierQuote.supplier.supplier_code')
                    ->label('Supplier')
                    ->badge()
                    ->color('info')
                    ->default('N/A')
                    ->placeholder('N/A'),

                TextInputColumn::make('quantity')
                    ->label('Qty')
                    ->type('number')
                    ->rules(['required', 'numeric', 'min:1'])
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('quantity_shipped')
                    ->label('Shipped')
                    ->alignCenter()
                    ->sortable()
                    ->default(0)
                    ->badge()
                    ->color(fn ($record) => $record->quantity_shipped >= $record->quantity ? 'success' : 'warning'),

                TextColumn::make('quantity_remaining')
                    ->label('Remaining')
                    ->alignCenter()
                    ->getStateUsing(fn ($record) => $record->quantity - ($record->quantity_shipped ?? 0))
                    ->badge()
                    ->color(fn ($state) => $state == 0 ? 'success' : 'info'),

                TextColumn::make('shipment_status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        $shipped = $record->quantity_shipped ?? 0;
                        if ($shipped == 0) return 'pending';
                        if ($shipped >= $record->quantity) return 'completed';
                        return 'partial';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'partial' => 'warning',
                        'pending' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextInputColumn::make('unit_price')
                    ->label('Unit Price')
                    ->type('number')
                    ->step(0.01)
                    ->rules(['required', 'numeric', 'min:0'])
                    ->sortable(),

                TextColumn::make('commission_percent')
                    ->label('Comm %')
                    ->suffix('%')
                    ->alignCenter()
                    ->toggleable(),

                TextColumn::make('commission_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'embedded' => 'success',
                        'separate' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->toggleable(),

                TextColumn::make('total')
                    ->label('Total')
                    ->money(fn () => $this->getOwnerRecord()->currency?->code ?? 'USD')
                    ->weight(FontWeight::Bold)
                    ->sortable(),

                TextColumn::make('product.weight')
                    ->label('Weight (kg)')
                    ->numeric(decimalPlaces: 2)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('product.volume')
                    ->label('Volume (m³)')
                    ->numeric(decimalPlaces: 4)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('supplierQuote.quote_number')
                    ->label('Source Quote')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->icon('heroicon-o-plus-circle'),
                
                // Dynamic PO creation buttons - one per supplier
                ...$this->getPurchaseOrderActions(),
            ])
            ->actions([
                Action::make('view_shipments')
                    ->label('Shipments')
                    ->icon('heroicon-o-truck')
                    ->color('info')
                    ->visible(fn ($record) => ($record->quantity_shipped ?? 0) > 0)
                    ->url(fn ($record) => route('filament.admin.resources.shipments.index', [
                        'tableFilters' => [
                            'proforma_invoice_item_id' => ['value' => $record->id]
                        ]
                    ]))
                    ->openUrlInNewTab(),

                EditAction::make()
                    ->icon('heroicon-o-pencil-square'),

                DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->before(function ($record, DeleteAction $action) {
                        // Prevent deletion if item has been shipped
                        if (($record->quantity_shipped ?? 0) > 0) {
                            Notification::make()
                                ->title('Cannot delete item')
                                ->body('This item has already been shipped and cannot be deleted.')
                                ->danger()
                                ->send();

                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function ($records, DeleteBulkAction $action) {
                            // Check if any record has been shipped
                            $hasShipped = $records->contains(fn ($record) => ($record->quantity_shipped ?? 0) > 0);
                            
                            if ($hasShipped) {
                                Notification::make()
                                    ->title('Cannot delete items')
                                    ->body('One or more items have already been shipped and cannot be deleted.')
                                    ->danger()
                                    ->send();

                                $action->cancel();
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * Generate dynamic PO creation actions - one button per supplier
     */
    protected function getPurchaseOrderActions(): array
    {
        $proformaInvoice = $this->getOwnerRecord();
        
        // Group items by supplier
        $itemsBySupplier = $proformaInvoice->items()
            ->with(['supplierQuote.supplier', 'product'])
            ->get()
            ->groupBy(function ($item) {
                return $item->supplierQuote?->supplier_id;
            })
            ->filter(fn ($items, $supplierId) => $supplierId !== null);
        
        if ($itemsBySupplier->isEmpty()) {
            return [];
        }
        
        $actions = [];
        
        foreach ($itemsBySupplier as $supplierId => $items) {
            $supplier = $items->first()->supplierQuote->supplier;
            $itemCount = $items->count();
            
            // Check if PO already exists for this supplier (simple check by supplier only)
            $lastPO = \App\Models\PurchaseOrder::where('supplier_id', $supplierId)
                ->orderBy('revision_number', 'desc')
                ->first();
            
            $nextRevision = $lastPO ? ($lastPO->revision_number + 1) : 1;
            $label = $lastPO 
                ? "Create PO for {$supplier->supplier_code} (Rev. {$nextRevision})"
                : "Create PO for {$supplier->supplier_code}";
            
            $actions[] = Action::make("create_po_{$supplierId}")
                ->label($label)
                ->icon('heroicon-o-document-plus')
                ->color($lastPO ? 'warning' : 'success')
                ->badge($itemCount)
                ->requiresConfirmation()
                ->modalHeading("Create Purchase Order for {$supplier->name}")
                ->modalDescription("This will create a Purchase Order with {$itemCount} item(s) for {$supplier->name}." . ($lastPO ? " This will be revision {$nextRevision}." : ""))
                ->modalSubmitActionLabel('Create PO')
                ->action(function () use ($supplierId, $items, $proformaInvoice, $nextRevision) {
                    $supplier = $items->first()->supplierQuote->supplier;
                    $supplierQuoteId = $items->first()->supplier_quote_id;
                    
                    // Create Purchase Order
                    $po = \App\Models\PurchaseOrder::create([
                        'supplier_id' => $supplierId,
                        'supplier_quote_id' => $supplierQuoteId,
                        'currency_id' => $proformaInvoice->currency_id,
                        'exchange_rate' => $proformaInvoice->exchange_rate ?? 1.0,
                        'payment_term_id' => $proformaInvoice->payment_term_id,
                        'incoterm' => $proformaInvoice->incoterm,
                        'incoterm_location' => $proformaInvoice->incoterm_location,
                        'status' => 'draft',
                        'revision_number' => $nextRevision,
                        'po_date' => now(),
                        'created_by' => auth()->id(),
                        'notes' => "Generated from Proforma Invoice {$proformaInvoice->proforma_number}",
                    ]);
                    
                    // Create PO Items
                    foreach ($items as $item) {
                        \App\Models\PurchaseOrderItem::create([
                            'purchase_order_id' => $po->id,
                            'product_id' => $item->product_id,
                            'quantity' => $item->quantity,
                            'unit_cost' => $item->unit_price,
                            'total_cost' => $item->total,
                            'product_name' => $item->product->name,
                            'product_sku' => $item->product->code ?? $item->product->sku,
                            'notes' => $item->notes,
                        ]);
                    }
                    
                    Notification::make()
                        ->success()
                        ->title('Purchase Order Created')
                        ->body("PO for {$supplier->name} created successfully (Revision {$nextRevision}).")
                        ->send();
                    
                    // Redirect to the created PO
                    return redirect()->route('filament.admin.resources.purchase-orders.edit', ['record' => $po->id]);
                });
        }
        
        return $actions;
    }
}
