<?php

namespace App\Filament\Resources\Orders\RelationManagers;


use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Order Items';



    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->label('Product')
                    ->relationship(
                        name: 'product',
                        titleAttribute: 'name',
                        modifyQueryUsing: function ($query) {
                            $order = $this->getOwnerRecord();
                            
                            // Get tags from Order (Tags for Suppliers field)
                            $orderTagIds = $order->tags()->pluck('tags.id')->toArray();
                            
                            // If order has tags, filter products by those tags
                            if (!empty($orderTagIds)) {
                                $query->whereHas('tags', function ($q) use ($orderTagIds) {
                                    $q->whereIn('tags.id', $orderTagIds);
                                });
                            }
                            
                            return $query->orderBy('name');
                        }
                    )
                    ->required()
                    ->searchable(['name', 'code', 'sku'])
                    ->preload()
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name} ({$record->code})")
                    ->helperText(function () {
                        $order = $this->getOwnerRecord();
                        
                        $orderTagIds = $order->tags()->pluck('tags.id')->toArray();
                        
                        if (empty($orderTagIds)) {
                            return 'No tags selected. All products are available.';
                        }
                        
                        $tagNames = $order->tags()->pluck('tags.name')->join(', ');
                        return "Filtered by tags: {$tagNames}";
                    })
                    ->live()
                    ->afterStateUpdated(function ($state, $set, $get) {
                        // Check for duplicate when product is selected
                        if ($state) {
                            $order = $this->getOwnerRecord();
                            $existingItem = $order->items()
                                ->where('product_id', $state)
                                ->first();
                            
                            if ($existingItem) {
                                $product = \App\Models\Product::find($state);
                                
Notification::make()
                                    ->danger()
                                    ->title('⚠️ ATTENTION: Product Already Exists!')
                                    ->body("**{$product->name}** (Code: {$product->code}) is already in this order with quantity **{$existingItem->quantity}**.\n\n**You can either:**\n\n✅ **Cancel** and increase the quantity of the existing item\n\n⚠️ **Continue** to add as separate line ONLY if it has different specifications (color, size, etc.)\n\n**Please acknowledge by closing this notification.**")
                                    ->persistent()
                                    ->duration(null)
                                    ->send();
                            }
                        }
                    })
                    ->columnSpan(2),

                TextInput::make('quantity')
                    ->label('Quantity')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->default(1)
                    ->suffix('units')
                    ->helperText('Number of units to order')
                    ->columnSpan(1),

                TextInput::make('requested_unit_price')
                    ->label('Target Price (Optional)')
                    ->helperText('Target price per unit')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('$')
                    ->step(0.01)
                    ->columnSpan(1),

                TextInput::make('commission_percent')
                    ->label('Commission %')
                    ->required()
                    ->numeric()
                    ->default(fn () => $this->getOwnerRecord()->commission_percent ?? 5.00)
                    ->minValue(0)
                    ->maxValue(99.99)
                    ->step(0.01)
                    ->suffix('%')
                    ->helperText('Commission for this product')
                    ->columnSpan(1),

                Select::make('commission_type')
                    ->options([
                        'embedded' => 'Embedded (included in price)',
                        'separate' => 'Separate (added to invoice)',
                    ])
                    ->required()
                    ->default(fn () => $this->getOwnerRecord()->commission_type ?? 'embedded')
                    ->helperText('How commission is applied')
                    ->columnSpan(1),

                Textarea::make('notes')
                    ->rows(2)
                    ->helperText('Add notes if this is a variant (different color, size, specs, etc.)')
                    ->columnSpanFull(),
            ])
            ->columns(4);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product.name')
            ->columns([
                TextColumn::make('product.code')
                    ->label(__('fields.code'))
                    ->searchable(),

                TextColumn::make('product.name')
                    ->searchable()
                    ->wrap(),

                TextInputColumn::make('quantity')
                    ->label('Quantity')
                    ->rules(['required', 'numeric', 'min:1'])
                    ->alignCenter()
                    ->sortable(),

                TextInputColumn::make('requested_unit_price')
                    ->label('Target Price')
                    ->rules(['nullable', 'numeric', 'min:0'])
                    ->placeholder('Not set')
                    ->prefix('$')
                    ->sortable(),

                TextInputColumn::make('commission_percent')
                    ->label('Commission %')
                    ->rules(['required', 'numeric', 'min:0', 'max:99.99'])
                    ->suffix('%')
                    ->alignCenter()
                    ->sortable(),

                SelectColumn::make('commission_type')
                    ->label('Type')
                    ->options([
                        'embedded' => 'Embedded',
                        'separate' => 'Separate',
                    ])
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('notes')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
                
                Action::make('bulk_add_items')
                    ->label('Bulk Add Items')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        CheckboxList::make('products')
                            ->label('Select Products')
                            ->options(function () {
                                $order = $this->getOwnerRecord();
                                
                                // Get tags from Order
                                $orderTagIds = $order->tags()->pluck('tags.id')->toArray();
                                
                                $query = \App\Models\Product::query();
                                
                                // Filter by tags if order has tags
                                if (!empty($orderTagIds)) {
                                    $query->whereHas('tags', function ($q) use ($orderTagIds) {
                                        $q->whereIn('tags.id', $orderTagIds);
                                    });
                                }
                                
                                // Exclude products already in order
                                $existingProductIds = $order->items()->pluck('product_id')->toArray();
                                if (!empty($existingProductIds)) {
                                    $query->whereNotIn('id', $existingProductIds);
                                }
                                
                                return $query->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(function ($product) {
                                        return [$product->id => "{$product->name} ({$product->code})"];
                                    });
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->columnSpanFull()
                            ->helperText('Select products to add to this order'),
                        
                        Grid::make(3)
                            ->schema([
                                TextInput::make('default_quantity')
                                    ->label('Default Quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required()
                                    ->helperText('Quantity for all selected products'),
                                
                                TextInput::make('commission_percent')
                                    ->label('Commission %')
                                    ->numeric()
                                    ->default(fn () => $this->getOwnerRecord()->commission_percent ?? 5.00)
                                    ->minValue(0)
                                    ->maxValue(99.99)
                                    ->step(0.01)
                                    ->suffix('%')
                                    ->required()
                                    ->helperText('Commission for all items'),
                                
                                Select::make('commission_type')
                                    ->options([
                                        'embedded' => 'Embedded',
                                        'separate' => 'Separate',
                                    ])
                                    ->default(fn () => $this->getOwnerRecord()->commission_type ?? 'embedded')
                                    ->required()
                                    ->helperText('How commission is applied'),
                            ])
                            ->visible(fn (Get $get) => !empty($get('products'))),
                    ])
                    ->action(function (array $data) {
                        $order = $this->getOwnerRecord();
                        $productIds = $data['products'];
                        $quantity = $data['default_quantity'];
                        $commissionPercent = $data['commission_percent'];
                        $commissionType = $data['commission_type'];
                        
                        $created = 0;
                        
                        foreach ($productIds as $productId) {
                            $order->items()->create([
                                'product_id' => $productId,
                                'quantity' => $quantity,
                                'commission_percent' => $commissionPercent,
                                'commission_type' => $commissionType,
                            ]);
                            $created++;
                        }
                        
                        Notification::make()
                            ->success()
                            ->title('Items Added')
                            ->body("{$created} item(s) added to the order successfully.")
                            ->send();
                    })
                    ->modalWidth('2xl'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
