<?php

namespace App\Filament\Resources\Orders\RelationManagers;


use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Radio;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Order Items';



    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
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
                            
                            return $query;
                        }
                    )
                    ->required()
                    ->searchable()
                    ->preload()
                    ->helperText(function () {
                        $order = $this->getOwnerRecord();
                        
                        $orderTagIds = $order->tags()->pluck('tags.id')->toArray();
                        
                        if (empty($orderTagIds)) {
                            return 'No tags selected. All products are available.';
                        }
                        
                        $tagNames = $order->tags()->pluck('tags.name')->join(', ');
                        return "Filtered by tags: {$tagNames}";
                    })
                    ->columnSpan(2),

                TextInput::make('quantity')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->default(1)
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
                    ->label('Code')
                    ->searchable(),

                TextColumn::make('product.name')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('quantity')
                    ->alignCenter(),

                TextColumn::make('requested_unit_price')
                    ->label('Target Price')
                    ->money(fn () => $this->getOwnerRecord()->currency?->code ?? 'USD')
                    ->placeholder('Not set'),

                TextColumn::make('commission_percent')
                    ->label('Commission')
                    ->suffix('%')
                    ->alignCenter(),

                TextColumn::make('commission_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'embedded' => 'success',
                        'separate' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->alignCenter(),

                TextColumn::make('notes')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->before(function (CreateAction $action, array $data) {
                        $order = $this->getOwnerRecord();
                        $productId = $data['product_id'];
                        
                        // Check if product already exists
                        $existingItem = $order->items()
                            ->where('product_id', $productId)
                            ->first();
                        
                        if ($existingItem) {
                            $product = \App\Models\Product::find($productId);
                            
                            // Show confirmation modal with options
                            $action->requiresConfirmation();
                            $action->modalHeading('⚠️ Product Already Exists');
                            $action->modalDescription(
                                "**{$product->name}** (Code: {$product->code}) is already in this order.\n\n" .
                                "**Existing item:** Quantity {$existingItem->quantity}\n" .
                                "**Adding:** Quantity {$data['quantity']}\n\n" .
                                "What would you like to do?"
                            );
                            $action->modalIcon('heroicon-o-exclamation-triangle');
                            $action->modalIconColor('warning');
                            
                            // Replace form with action selection
                            $action->form([
                                Radio::make('duplicate_action')
                                    ->label('Choose Action')
                                    ->options([
                                        'merge' => "Merge quantities (Total: " . ($existingItem->quantity + $data['quantity']) . ")",
                                        'separate' => "Add as separate line (for different color/specs/etc.)",
                                    ])
                                    ->descriptions([
                                        'merge' => '✅ Recommended for identical products',
                                        'separate' => '⚠️ Only if product has different characteristics',
                                    ])
                                    ->required()
                                    ->default('merge')
                                    ->live(),
                            ]);
                            
                            $action->modalSubmitActionLabel('Confirm');
                        }
                    })
                    ->using(function (array $data, CreateAction $action): ?\Illuminate\Database\Eloquent\Model {
                        $order = $this->getOwnerRecord();
                        $productId = $data['product_id'];
                        
                        // Check if product already exists
                        $existingItem = $order->items()
                            ->where('product_id', $productId)
                            ->first();
                        
                        if ($existingItem && isset($data['duplicate_action'])) {
                            $duplicateAction = $data['duplicate_action'];
                            
                            if ($duplicateAction === 'merge') {
                                // Merge quantities
                                $existingItem->quantity += $data['quantity'];
                                $existingItem->save();
                                
                                $oldQuantity = $existingItem->quantity - $data['quantity'];
                                
                                Notification::make()
                                    ->title('✅ Quantity Updated')
                                    ->body("Product quantity increased from {$oldQuantity} to {$existingItem->quantity}")
                                    ->success()
                                    ->send();
                                
                                // Return existing item (don't create new)
                                return null;
                            }
                            // If 'separate', continue with normal creation below
                        }
                        
                        // Normal creation (no duplicate or user chose 'separate')
                        return $order->items()->create($data);
                    }),
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
