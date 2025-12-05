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
use Filament\Tables\Table;

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
                            
                            // If order has a category, filter products by that category
                            if ($order->category_id) {
                                $query->where('category_id', $order->category_id);
                            }
                            
                            return $query;
                        }
                    )
                    ->required()
                    ->searchable()
                    ->preload()
                    ->helperText(function () {
                        $order = $this->getOwnerRecord();
                        
                        if (!$order->category_id) {
                            return 'No category selected. All products are available.';
                        }
                        
                        $categoryName = $order->category?->name;
                        return "Filtered by category: {$categoryName}";
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
                CreateAction::make(),
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
