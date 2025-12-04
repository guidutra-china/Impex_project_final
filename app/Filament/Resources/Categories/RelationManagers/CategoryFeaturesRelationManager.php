<?php

namespace App\Filament\Resources\Categories\RelationManagers;

use App\Repositories\CategoryRepository;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use BackedEnum;

class CategoryFeaturesRelationManager extends RelationManager
{
    protected static string $relationship = 'categoryFeatures';

    protected static ?string $title = 'Feature Templates';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $recordTitleAttribute = 'feature_name';

    protected CategoryRepository $repository;

    public function mount(): void {
        parent::mount();
        $this->repository = app(CategoryRepository::class);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                $this->repository->getFeaturesQuery($this->getOwnerRecord()->id)
            )
            ->columns([
                TextColumn::make('feature_name')
                    ->label('Feature Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('default_value')
                    ->label('Default Value')
                    ->default('-')
                    ->placeholder('(no default)'),

                TextColumn::make('unit')
                    ->label('Unit')
                    ->default('-')
                    ->badge()
                    ->color('gray'),

                IconColumn::make('is_required')
                    ->label('Required')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add Feature Template')
                    ->modalHeading('Add Feature Template')
                    ->modalDescription('Define a feature that will be automatically added to all new products in this category'),
            ])
            ->actions([
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
            ->emptyStateHeading('No feature templates')
            ->emptyStateDescription('Add feature templates that will be automatically applied to products in this category')
            ->emptyStateIcon('heroicon-o-sparkles');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('feature_name')
                    ->label('Feature Name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g., Material, Voltage, Capacity')
                    ->helperText('The name of the feature (e.g., "Material", "Color", "Power")'),

                TextInput::make('default_value')
                    ->label('Default Value')
                    ->maxLength(255)
                    ->placeholder('e.g., Stainless Steel, 220V')
                    ->helperText('Optional default value that will be pre-filled'),

                TextInput::make('unit')
                    ->label('Unit of Measure')
                    ->maxLength(255)
                    ->placeholder('e.g., kg, V, W, cm')
                    ->helperText('Optional unit (leave empty if not applicable)'),

                Checkbox::make('is_required')
                    ->label('Required Feature')
                    ->default(false)
                    ->helperText('If checked, this feature must be filled in for products'),

                TextInput::make('sort_order')
                    ->label('Sort Order')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->helperText('Lower numbers appear first'),
            ]);
    }
}
