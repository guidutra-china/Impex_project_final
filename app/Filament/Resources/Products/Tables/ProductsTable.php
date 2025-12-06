<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Checkbox;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->label('Avatar')
                    ->circular()
                    ->size(40)
                    ->default('No Picture'),

                TextColumn::make('name')
                    ->label('Product Name')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->weight('bold'),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('SKU copied')
                    ->copyMessageDuration(1500)
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true), // Hidden by default

                TextColumn::make('supplier_code')
                    ->label('Supplier Code')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('info')
                    ->default('-'),

                TextColumn::make('customer_code')
                    ->label('Customer Code')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('success')
                    ->default('-'),

                TextColumn::make('tags.name')
                    ->label('Tag')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->default('-'),

                TextColumn::make('brand')
                    ->label('Family')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->default('-'),

                TextColumn::make('price')
                    ->label('Price')
                    ->sortable()
                    ->money(fn ($record) => $record->currency?->code ?? 'USD', divideBy: 100)
                    ->default('-'),

                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable()
                    ->limit(20)
                    ->toggleable()
                    ->default('-'),

                TextColumn::make('client.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->limit(10)
                    ->toggleable()
                    ->default('-'),

                SelectColumn::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ])
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('moq')
                    ->label('MOQ')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn ($state, $record) => $state ? "{$state} {$record->moq_unit}" : '-'),


                TextColumn::make('lead_time_days')
                    ->label('Lead Time')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn ($state) => $state ? "{$state} days" : '-'),

                TextColumn::make('hs_code')
                    ->label('HS Code')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->default('-'),

                TextColumn::make('origin_country')
                    ->label('Origin')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->default('-'),

                TextColumn::make('model_number')
                    ->label('Model')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->default('-'),

                TextColumn::make('net_weight')
                    ->label('Net Weight')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn ($state) => $state ? "{$state} kg" : '-'),

                TextColumn::make('gross_weight')
                    ->label('Gross Weight')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn ($state) => $state ? "{$state} kg" : '-'),

                TextColumn::make('pcs_per_carton')
                    ->label('Pcs/Carton')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->default('-'),

                TextColumn::make('carton_cbm')
                    ->label('Carton CBM')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn ($state) => $state ? "{$state} m³" : '-'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tags')
                    ->relationship('tags', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),

                SelectFilter::make('supplier')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('client')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Customer'),

                SelectFilter::make('origin_country')
                    ->label('Country of Origin')
                    ->options(fn () => \App\Models\Product::query()
                        ->whereNotNull('origin_country')
                        ->distinct()
                        ->pluck('origin_country', 'origin_country')
                        ->toArray()
                    )
                    ->searchable(),

                TernaryFilter::make('has_moq')
                    ->label('Has MOQ')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('moq'),
                        false: fn ($query) => $query->whereNull('moq'),
                    ),

                TernaryFilter::make('has_photos')
                    ->label('Has Photos')
                    ->queries(
                        true: fn ($query) => $query->whereHas('photos'),
                        false: fn ($query) => $query->whereDoesntHave('photos'),
                    ),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                ViewAction::make()
                    ->label('View')
                    ->icon('heroicon-o-eye'),
                EditAction::make()
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square'),
                Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('warning')
                    ->form([
                        Checkbox::make('bom_items')
                            ->label('Duplicate BOM Items')
                            ->helperText('Copy all bill of materials components to the new product')
                            ->default(true)
                            ->inline(false),
                        
                        Checkbox::make('features')
                            ->label('Duplicate Features')
                            ->helperText('Copy all product features to the new product')
                            ->default(true)
                            ->inline(false),
                        
                        Checkbox::make('tags')
                            ->label('Duplicate Tags')
                            ->helperText('Copy all tags to the new product')
                            ->default(true)
                            ->inline(false),
                        
                        Checkbox::make('avatar')
                            ->label('Duplicate Avatar Image')
                            ->helperText('Copy the product image to the new product')
                            ->default(true)
                            ->inline(false),
                    ])
                    ->modalHeading('Duplicate Product')
                    ->modalDescription('Choose what to duplicate from this product. The new product will have "(Copy)" added to its name and the SKU will be cleared.')
                    ->modalSubmitActionLabel('Duplicate Product')
                    ->modalWidth('md')
                    ->action(function (\App\Models\Product $record, array $data) {
                        try {
                            // Prepare duplication options
                            $options = [
                                'bom_items' => $data['bom_items'] ?? false,
                                'features' => $data['features'] ?? false,
                                'tags' => $data['tags'] ?? false,
                                'avatar' => $data['avatar'] ?? false,
                            ];

                            // Duplicate the product with selected options
                            $newProduct = $record->duplicate($options);
                            
                            // Build notification message
                            $duplicatedItems = [];
                            if ($options['bom_items'] && $record->bomItems()->count() > 0) {
                                $duplicatedItems[] = $record->bomItems()->count() . ' BOM items';
                            }
                            if ($options['features'] && $record->features()->count() > 0) {
                                $duplicatedItems[] = $record->features()->count() . ' features';
                            }
                            if ($options['tags'] && $record->tags()->count() > 0) {
                                $duplicatedItems[] = $record->tags()->count() . ' tags';
                            }
                            if ($options['avatar'] && $record->avatar) {
                                $duplicatedItems[] = 'avatar image';
                            }

                            $message = "New product '{$newProduct->name}' has been created.";
                            if (!empty($duplicatedItems)) {
                                $message .= " Duplicated: " . implode(', ', $duplicatedItems) . ".";
                            }
                            
                            Notification::make()
                                ->title('Product duplicated successfully')
                                ->body($message)
                                ->success()
                                ->duration(5000)
                                ->send();
                            
                            // Redirect to edit the new product
                            return redirect()->route('filament.admin.resources.products.edit', ['record' => $newProduct->id]);
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error duplicating product')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);


    }
}
