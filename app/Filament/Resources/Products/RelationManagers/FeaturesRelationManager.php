<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\CategoryFeature;
use App\Repositories\ProductRepository;
use App\Repositories\CategoryRepository;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use BackedEnum;

class FeaturesRelationManager extends RelationManager
{
    protected static string $relationship = 'features';

    protected static ?string $title = 'Product Features';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected ProductRepository $productRepository;
    protected CategoryRepository $categoryRepository;

    public function mount(): void {
        parent::mount();
        $this->productRepository = app(ProductRepository::class);
        $this->categoryRepository = app(CategoryRepository::class);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('feature_name')
            ->columns([
                TextColumn::make('feature_name')
                    ->label('Feature Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('feature_value')
                    ->label('Value')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('unit')
                    ->label('Unit')
                    ->default('-')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add Feature')
                    ->after(function ($record) {
                        $product = $this->getOwnerRecord();
                        
                        // Auto-populate from category templates when creating first time
                        if ($product->features()->count() === 1 && $product->category_id) {
                            $category = $product->category;
                            
                            if ($category && $category->categoryFeatures->isNotEmpty()) {
                                foreach ($category->categoryFeatures as $template) {
                                    // Skip if already exists
                                    if ($product->features()->where('feature_name', $template->feature_name)->exists()) {
                                        continue;
                                    }
                                    
                                    $product->features()->create([
                                        'feature_name' => $template->feature_name,
                                        'feature_value' => $template->default_value ?? '',
                                        'unit' => $template->unit,
                                        'sort_order' => $template->sort_order,
                                    ]);
                                }
                                
                                Notification::make()
                                    ->title('Features loaded from category')
                                    ->body("Added {$category->categoryFeatures->count()} feature templates from {$category->name}")
                                    ->success()
                                    ->send();
                            }
                        }
                    }),
            ])
            ->actions([
                Action::make('save_to_category')
                    ->label('Save to Category')
                    ->icon('heroicon-o-bookmark')
                    ->color('warning')
                    ->visible(fn () => $this->getOwnerRecord()->category_id !== null)
                    ->requiresConfirmation()
                    ->modalHeading('Save Feature to Category Template')
                    ->modalDescription(fn ($record) => "This will add \"{$record->feature_name}\" as a standard feature template for all future products in this category.")
                    ->action(function ($record) {
                        $product = $this->getOwnerRecord();
                        
                        if (!$product->category_id) {
                            Notification::make()
                                ->title('No category selected')
                                ->body('Product must have a category to save features to template')
                                ->warning()
                                ->send();
                            return;
                        }
                        
                        // Check if feature already exists in category
                        $exists = CategoryFeature::where('category_id', $product->category_id)
                            ->where('feature_name', $record->feature_name)
                            ->exists();
                        
                        if ($exists) {
                            Notification::make()
                                ->title('Feature already exists')
                                ->body("This feature is already a template in {$product->category->name}")
                                ->warning()
                                ->send();
                            return;
                        }
                        
                        // Create category feature template
                        CategoryFeature::create([
                            'category_id' => $product->category_id,
                            'feature_name' => $record->feature_name,
                            'default_value' => $record->feature_value,
                            'unit' => $record->unit,
                            'sort_order' => $record->sort_order,
                            'is_required' => false,
                        ]);
                        
                        Notification::make()
                            ->title('Feature saved to category')
                            ->body("Added \"{$record->feature_name}\" to {$product->category->name} templates")
                            ->success()
                            ->send();
                    }),

                EditAction::make(),

                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order')
            ->emptyStateHeading('No features added')
            ->emptyStateDescription('Add product features and specifications here')
            ->emptyStateIcon('heroicon-o-sparkles');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('feature_name')
                    ->label('Feature Name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g., Material, Color, Voltage, Power')
                    ->helperText('The name of the product feature or specification'),

                TextInput::make('feature_value')
                    ->label('Value')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g., Stainless Steel, Red, 220V, 1500W')
                    ->helperText('The value or specification'),

                TextInput::make('unit')
                    ->label('Unit of Measure')
                    ->maxLength(255)
                    ->placeholder('e.g., kg, cm, V, W, °C, %')
                    ->helperText('Optional unit (leave empty if not applicable)'),

                TextInput::make('sort_order')
                    ->label('Sort Order')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->helperText('Lower numbers appear first'),
            ]);
    }
}
