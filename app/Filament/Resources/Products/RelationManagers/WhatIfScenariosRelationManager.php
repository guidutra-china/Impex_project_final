<?php

namespace App\Filament\Resources\SupplierQuotes\Products\RelationManagers;

use App\Models\WhatIfScenario;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use BackedEnum;

class WhatIfScenariosRelationManager extends RelationManager
{
    protected static string $relationship = 'whatIfScenarios';

    protected static ?string $title = 'What-If Scenarios';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBeaker;

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Scenario Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('scenario_total_cost')
                    ->label('Scenario Cost')
                    ->money('USD', divideBy: 100)
                    ->sortable()
                    ->color('info'),

                TextColumn::make('cost_difference')
                    ->label('vs Current')
                    ->money('USD', divideBy: 100)
                    ->sortable()
                    ->color(fn ($record) => $record->reducesCost() ? 'success' : 'danger')
                    ->icon(fn ($record) => $record->reducesCost() ? 'heroicon-o-arrow-trending-down' : 'heroicon-o-arrow-trending-up')
                    ->iconPosition('after')
                    ->weight('bold'),

                TextColumn::make('cost_difference_percentage')
                    ->label('Change %')
                    ->formatStateUsing(fn ($state) => number_format($state, 2) . '%')
                    ->sortable()
                    ->color(fn ($record) => $record->reducesCost() ? 'success' : 'danger'),

                TextColumn::make('scenario_selling_price')
                    ->label('Selling Price')
                    ->money('USD', divideBy: 100)
                    ->toggleable(),

                TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->default('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Create Scenario')
                    ->modalHeading('Create What-If Scenario')
                    ->modalDescription('Test different cost scenarios without affecting actual data')
                    ->modalWidth('3xl')
                    ->form([
                        TextInput::make('name')
                            ->label('Scenario Name')
                            ->required()
                            ->placeholder('e.g., 10% Cost Reduction, New Supplier')
                            ->maxLength(255),

                        Textarea::make('description')
                            ->label('Description')
                            ->rows(2)
                            ->placeholder('Describe what this scenario tests')
                            ->columnSpan(2),

                        Repeater::make('component_adjustments')
                            ->label('Component Cost Adjustments')
                            ->schema([
                                Select::make('component_id')
                                    ->label('Component')
                                    ->options(function () {
                                        $product = $this->getOwnerRecord();
                                        return $product->bomItems()
                                            ->with('component')
                                            ->get()
                                            ->pluck('component.name', 'component_id');
                                    })
                                    ->required()
                                    ->searchable(),

                                TextInput::make('new_cost')
                                    ->label('New Cost')
                                    ->numeric()
                                    ->prefix('$')
                                    ->step(0.01)
                                    ->required(),
                            ])
                            ->columns(2)
                            ->columnSpan(2)
                            ->collapsible()
                            ->defaultItems(0),

                        TextInput::make('labor_cost_adjustment')
                            ->label('Direct Labor Cost Override')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->helperText('Leave empty to use current value'),

                        TextInput::make('overhead_cost_adjustment')
                            ->label('Direct Overhead Cost Override')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->helperText('Leave empty to use current value'),

                        TextInput::make('markup_adjustment')
                            ->label('Markup % Override')
                            ->numeric()
                            ->suffix('%')
                            ->step(0.1)
                            ->helperText('Leave empty to use current value'),
                    ])
                    ->action(function (array $data) {
                        $product = $this->getOwnerRecord();

                        // Convert component adjustments to array
                        $componentCostAdjustments = [];
                        if (!empty($data['component_adjustments'])) {
                            foreach ($data['component_adjustments'] as $adjustment) {
                                $componentCostAdjustments[$adjustment['component_id']] = (int) ($adjustment['new_cost'] * 100);
                            }
                        }

                        // Create scenario
                        $scenario = WhatIfScenario::create([
                            'product_id' => $product->id,
                            'created_by' => auth()->id(),
                            'name' => $data['name'],
                            'description' => $data['description'] ?? null,
                            'component_cost_adjustments' => $componentCostAdjustments,
                            'labor_cost_adjustment' => !empty($data['labor_cost_adjustment']) ? (int) ($data['labor_cost_adjustment'] * 100) : null,
                            'overhead_cost_adjustment' => !empty($data['overhead_cost_adjustment']) ? (int) ($data['overhead_cost_adjustment'] * 100) : null,
                            'markup_adjustment' => $data['markup_adjustment'] ?? null,
                        ]);

                        // Calculate scenario
                        $scenario->calculate();

                        Notification::make()
                            ->title('Scenario created')
                            ->body("Scenario \"{$scenario->name}\" calculated successfully")
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                ViewAction::make()
                    ->modalHeading(fn ($record) => "Scenario: {$record->name}")
                    ->modalContent(fn ($record) => view('filament.resources.products.what-if-scenario-details', [
                        'scenario' => $record->load('product'),
                    ])),

                Action::make('recalculate')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->action(function ($record) {
                        $record->calculate();

                        Notification::make()
                            ->title('Scenario recalculated')
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
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No what-if scenarios')
            ->emptyStateDescription('Create scenarios to test different cost assumptions')
            ->emptyStateIcon('heroicon-o-beaker');
    }


    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Scenario Name')
                    ->required()
                    ->maxLength(255),

                Textarea::make('description')
                    ->label('Description')
                    ->rows(2),
            ]);
    }
}
