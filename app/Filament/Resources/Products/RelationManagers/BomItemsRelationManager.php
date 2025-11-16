<?php

namespace App\Filament\Resources\SupplierQuotes\Products\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use BackedEnum;

class BomItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'bomItems';

    protected static ?string $title = 'Bill of Materials (BOM)';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('component.name')
            ->columns([
                TextColumn::make('component.code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('gray')
                    ->weight('bold'),

                TextColumn::make('component.name')
                    ->label('Component Name')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->component->name),

                TextColumn::make('component.type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'raw_material' => 'info',
                        'purchased_part' => 'success',
                        'sub_assembly' => 'warning',
                        'packaging' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'raw_material' => 'Raw Material',
                        'purchased_part' => 'Purchased',
                        'sub_assembly' => 'Sub-Assembly',
                        'packaging' => 'Packaging',
                        default => ucfirst($state),
                    }),

                TextColumn::make('quantity')
                    ->label('Qty')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(fn ($record) => ' ' . $record->unit_of_measure)
                    ->sortable(),

                TextColumn::make('waste_factor')
                    ->label('Waste %')
                    ->numeric(decimalPlaces: 1)
                    ->suffix('%')
                    ->sortable()
                    ->default('0')
                    ->toggleable(),

                TextColumn::make('actual_quantity')
                    ->label('Actual Qty')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(fn ($record) => ' ' . $record->unit_of_measure)
                    ->sortable()
                    ->description('Quantity + Waste')
                    ->toggleable(),

                TextColumn::make('unit_cost')
                    ->label('Unit Cost')
                    ->money('USD', divideBy: 100)
                    ->sortable(),

                TextColumn::make('total_cost')
                    ->label('Total Cost')
                    ->money('USD', divideBy: 100)
                    ->sortable()
                    ->weight('bold')
                    ->color('success')
                    ->description('Actual Qty × Unit Cost'),

                IconColumn::make('is_optional')
                    ->label('Optional')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Action::make('export_pdf')
                    ->label('Export PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->action(function () {
                        $product = $this->getOwnerRecord();
                        $exportService = app(\App\Services\BomExportService::class);
                        $path = $exportService->exportCurrentBomToPdf($product);

                        return response()->download(storage_path('app/' . $path));
                    }),

                Action::make('export_excel')
                    ->label('Export Excel')
                    ->icon('heroicon-o-table-cells')
                    ->color('success')
                    ->action(function () {
                        $product = $this->getOwnerRecord();
                        $exportService = app(\App\Services\BomExportService::class);
                        $path = $exportService->exportCurrentBomToExcel($product);

                        return response()->download(storage_path('app/' . $path));
                    }),

                CreateAction::make()
                    ->label('Add Component')
                    ->modalHeading('Add Component to BOM')
                    ->modalDescription('Add a component to this product\'s bill of materials')
                    ->successNotification(
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Component Added')
                            ->body('BOM costs have been recalculated automatically.')
                    )
                    ->after(function () {
                        $product = $this->getOwnerRecord();
                        $product->calculateAndUpdateCosts();
                        $product->refresh();

                        // Emit event to refresh parent form
                        $this->dispatch('refresh-product-costs');
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->successNotification(
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Component Updated')
                            ->body('BOM costs have been recalculated automatically.')
                    )
                    ->after(function () {
                        $product = $this->getOwnerRecord();
                        $product->calculateAndUpdateCosts();
                        $product->refresh();

                        // Emit event to refresh parent form
                        $this->dispatch('refresh-product-costs');
                    }),
                DeleteAction::make()
                    ->successNotification(
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Component Deleted')
                            ->body('BOM costs have been recalculated automatically.')
                    )
                    ->after(function () {
                        $product = $this->getOwnerRecord();
                        $product->calculateAndUpdateCosts();
                        $product->refresh();

                        // Emit event to refresh parent form
                        $this->dispatch('refresh-product-costs');
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->successNotification(
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Components Deleted')
                                ->body('BOM costs have been recalculated automatically.')
                        )
                        ->after(function () {
                            $product = $this->getOwnerRecord();
                            $product->calculateAndUpdateCosts();
                            $product->refresh();

                            // Emit event to refresh parent form
                            $this->dispatch('refresh-product-costs');
                        }),
                ]),
            ])
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order')
            ->emptyStateHeading('No components in BOM')
            ->emptyStateDescription('Add components to define what this product is made of')
            ->emptyStateIcon('heroicon-o-queue-list');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('component_id')
                    ->label('Component')
                    ->relationship('component', 'name', fn ($query) => $query->active()->orderBy('code'))
                    ->searchable(['code', 'name'])
                    ->preload()
                    ->required()
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->code} - {$record->name}")
                    ->helperText('Select the component to add to this product')
                    ->columnSpan(2)
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if (!$state) return;

                        $component = \App\Models\Component::find($state);
                        if ($component) {
                            $set('unit_of_measure', $component->unit_of_measure);
                        }
                    }),

                TextInput::make('quantity')
                    ->label('Quantity Required')
                    ->numeric()
                    ->required()
                    ->default(1)
                    ->step(0.0001)
                    ->minValue(0.0001)
                    ->helperText('Quantity needed per product unit')
                    ->live(onBlur: true),

                TextInput::make('unit_of_measure')
                    ->label('Unit of Measure')
                    ->required()
                    ->default('pcs')
                    ->maxLength(255)
                    ->placeholder('e.g., pcs, kg, m, L')
                    ->helperText('Unit for quantity'),

                TextInput::make('waste_factor')
                    ->label('Waste Factor (%)')
                    ->numeric()
                    ->default(0)
                    ->step(0.1)
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%')
                    ->helperText('Scrap/waste percentage (0-100)')
                    ->live(onBlur: true),

                Toggle::make('is_optional')
                    ->label('Optional Component')
                    ->default(false)
                    ->helperText('Check if this component is optional'),

                TextInput::make('sort_order')
                    ->label('Sort Order')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->helperText('Display order (lower numbers first)'),

                Textarea::make('notes')
                    ->label('Notes')
                    ->rows(3)
                    ->maxLength(1000)
                    ->placeholder('Additional notes or instructions')
                    ->columnSpan(2),
            ])
            ->columns(2);
    }
}
