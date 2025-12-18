<?php

namespace App\Filament\Resources\CustomerQuotes\RelationManagers;

use App\Models\CustomerQuoteProductSelection;
use App\Models\QuoteItem;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductSelectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'productSelections';

    protected static ?string $title = 'Product Selection & Visibility';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Toggle::make('is_visible_to_customer')
                    ->label('Visible to Customer')
                    ->helperText('If disabled, this product will not be shown to the customer')
                    ->default(true),

                Textarea::make('custom_notes')
                    ->label('Custom Notes')
                    ->rows(3)
                    ->helperText('Optional notes for this specific product in the quote')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['quoteItem.product', 'quoteItem.supplierQuote.supplier']))
            ->columns([
                TextColumn::make('quoteItem.product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->quoteItem->product->code ?? null),

                TextColumn::make('quoteItem.supplierQuote.supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('quoteItem.unit_price_after_commission')
                    ->label('Unit Price')
                    ->money('USD', divideBy: 100)
                    ->sortable(),

                TextColumn::make('quoteItem.quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('total_price')
                    ->label('Total')
                    ->state(fn ($record) => $record->quoteItem->unit_price_after_commission * $record->quoteItem->quantity)
                    ->money('USD', divideBy: 100)
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                TextColumn::make('quoteItem.lead_time_days')
                    ->label('Lead Time')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return 'N/A';
                        if ($state < 7) return "{$state} days";
                        if ($state < 30) return ceil($state / 7) . ' weeks';
                        return ceil($state / 30) . ' months';
                    })
                    ->icon('heroicon-o-clock')
                    ->toggleable(),

                IconColumn::make('is_visible_to_customer')
                    ->label('Visible')
                    ->boolean()
                    ->sortable()
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-eye-slash')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('display_order')
                    ->label('Order')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_visible_to_customer')
                    ->label('Visible to Customer')
                    ->placeholder('All products')
                    ->trueLabel('Visible only')
                    ->falseLabel('Hidden only'),
            ])
            ->headerActions([
                Action::make('add_all_products')
                    ->label('Add All Products from Selected Suppliers')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->action(function () {
                        $customerQuote = $this->getOwnerRecord();
                        
                        // Get all supplier quotes from customer quote items
                        $supplierQuoteIds = $customerQuote->items()->pluck('supplier_quote_id')->unique();
                        
                        // Get all quote items from these supplier quotes
                        $quoteItems = QuoteItem::whereIn('supplier_quote_id', $supplierQuoteIds)->get();
                        
                        $added = 0;
                        $displayOrder = $customerQuote->productSelections()->max('display_order') ?? 0;
                        
                        foreach ($quoteItems as $quoteItem) {
                            // Check if already exists
                            $exists = $customerQuote->productSelections()
                                ->where('quote_item_id', $quoteItem->id)
                                ->exists();
                            
                            if (!$exists) {
                                CustomerQuoteProductSelection::create([
                                    'customer_quote_id' => $customerQuote->id,
                                    'quote_item_id' => $quoteItem->id,
                                    'is_visible_to_customer' => true,
                                    'display_order' => ++$displayOrder,
                                ]);
                                $added++;
                            }
                        }
                        
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title("Added {$added} products")
                            ->send();
                    }),

                Action::make('show_all')
                    ->label('Show All to Customer')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->action(function () {
                        $this->getOwnerRecord()->productSelections()->update(['is_visible_to_customer' => true]);
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('All products are now visible to customer')
                            ->send();
                    }),

                Action::make('hide_all')
                    ->label('Hide All from Customer')
                    ->icon('heroicon-o-eye-slash')
                    ->color('danger')
                    ->action(function () {
                        $this->getOwnerRecord()->productSelections()->update(['is_visible_to_customer' => false]);
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('All products are now hidden from customer')
                            ->send();
                    }),
            ])
            ->actions([
                Action::make('toggle_visibility')
                    ->label(fn ($record) => $record->is_visible_to_customer ? 'Hide' : 'Show')
                    ->icon(fn ($record) => $record->is_visible_to_customer ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn ($record) => $record->is_visible_to_customer ? 'warning' : 'success')
                    ->action(function ($record) {
                        $record->update(['is_visible_to_customer' => !$record->is_visible_to_customer]);
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title($record->is_visible_to_customer ? 'Product is now visible' : 'Product is now hidden')
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('show_to_customer')
                        ->label('Show to Customer')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->update(['is_visible_to_customer' => true]);
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Selected products are now visible')
                                ->send();
                        }),

                    BulkAction::make('hide_from_customer')
                        ->label('Hide from Customer')
                        ->icon('heroicon-o-eye-slash')
                        ->color('warning')
                        ->action(function ($records) {
                            $records->each->update(['is_visible_to_customer' => false]);
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Selected products are now hidden')
                                ->send();
                        }),

                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('display_order', 'asc')
            ->reorderable('display_order')
            ->emptyStateHeading('No products selected yet')
            ->emptyStateDescription('Click "Add All Products from Selected Suppliers" to start selecting products for this quote.')
            ->emptyStateIcon('heroicon-o-inbox');
    }
}
