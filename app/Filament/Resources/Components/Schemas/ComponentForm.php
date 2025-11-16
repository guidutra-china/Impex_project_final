<?php

namespace App\Filament\Resources\Components\Schemas;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ComponentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                // Basic Information Section
                Section::make('Basic Information')
                    ->schema([
                        Select::make('type')
                            ->label('Component Type')
                            ->options([
                                'raw_material' => 'Raw Material',
                                'purchased_part' => 'Purchased Part',
                                'sub_assembly' => 'Sub-Assembly',
                                'packaging' => 'Packaging',
                            ])
                            ->default('raw_material')
                            ->required()
                            ->searchable(),

                        TextInput::make('code')
                            ->label('Component Code / Part Number')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Unique identifier (SKU/Part Number)'),

                        TextInput::make('name')
                            ->label('Component Name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpan(3),
                    ])
                    ->columns(3)
                    ->columnSpan(2),

                // Relationships Section
                Section::make('Supplier & Category')
                    ->schema([
                        Select::make('supplier_id')
                            ->label('Supplier')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->helperText('Primary supplier for this component'),

                        Select::make('category_id')
                            ->label('Category')
                            ->relationship('category', 'name', fn ($query) => $query->active()->ordered())
                            ->searchable()
                            ->preload()
                            ->helperText('Optional category classification'),
                    ])
                    ->columns(2)
                    ->columnSpan(2)
                    ->collapsible(),

                // Cost Information Section
                Section::make('Cost Information')
                    ->description('All costs are per unit')
                    ->schema([
                        Select::make('currency_id')
                            ->label('Currency')
                            ->relationship('currency', 'name', fn ($query) => $query->where('is_active', true))
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->code} ({$record->symbol}) - {$record->name}")
                            ->searchable(['code', 'name'])
                            ->preload()
                            ->required()
                            ->live()
                            ->helperText('Select the currency for cost tracking')
                            ->columnSpan(3),

                        TextInput::make('unit_cost')
                            ->label('Material Cost per Unit')
                            ->numeric()
                            ->prefix(function ($get) {
                                $currencyId = $get('currency_id');
                                if ($currencyId) {
                                    $currency = \App\Models\Currency::find($currencyId);
                                    return $currency?->symbol ?? '$';
                                }
                                return '$';
                            })
                            ->step(0.01)
                            ->default(0)
                            ->helperText('Base material/purchase cost')
                            ->dehydrateStateUsing(fn ($state) => $state ? (int) ($state * 100) : 0)
                            ->formatStateUsing(fn ($state) => $state ? $state / 100 : 0),

                        TextInput::make('labor_cost_per_unit')
                            ->label('Labor Cost per Unit')
                            ->numeric()
                            ->prefix(function ($get) {
                                $currencyId = $get('currency_id');
                                if ($currencyId) {
                                    $currency = \App\Models\Currency::find($currencyId);
                                    return $currency?->symbol ?? '$';
                                }
                                return '$';
                            })
                            ->step(0.01)
                            ->default(0)
                            ->helperText('Manufacturing/assembly labor')
                            ->dehydrateStateUsing(fn ($state) => $state ? (int) ($state * 100) : 0)
                            ->formatStateUsing(fn ($state) => $state ? $state / 100 : 0),

                        TextInput::make('overhead_cost_per_unit')
                            ->label('Overhead Cost per Unit')
                            ->numeric()
                            ->prefix(function ($get) {
                                $currencyId = $get('currency_id');
                                if ($currencyId) {
                                    $currency = \App\Models\Currency::find($currencyId);
                                    return $currency?->symbol ?? '$';
                                }
                                return '$';
                            })
                            ->step(0.01)
                            ->default(0)
                            ->helperText('Factory overhead, utilities, etc.')
                            ->dehydrateStateUsing(fn ($state) => $state ? (int) ($state * 100) : 0)
                            ->formatStateUsing(fn ($state) => $state ? $state / 100 : 0),
                    ])
                    ->columns(3)
                    ->columnSpan(2),

                // Stock Management Section
                Section::make('Stock Management')
                    ->schema([
                        TextInput::make('unit_of_measure')
                            ->label('Unit of Measure')
                            ->required()
                            ->default('pcs')
                            ->maxLength(255)
                            ->placeholder('e.g., pcs, kg, m, L, ft')
                            ->helperText('Unit for quantity tracking'),

                        TextInput::make('stock_quantity')
                            ->label('Current Stock Quantity')
                            ->numeric()
                            ->default(0)
                            ->step(0.001)
                            ->minValue(0)
                            ->suffix(fn ($get) => $get('unit_of_measure') ?? 'units'),

                        TextInput::make('reorder_level')
                            ->label('Reorder Level')
                            ->numeric()
                            ->step(0.001)
                            ->minValue(0)
                            ->helperText('Minimum stock level before reorder')
                            ->suffix(fn ($get) => $get('unit_of_measure') ?? 'units'),

                        TextInput::make('lead_time_days')
                            ->label('Lead Time (Days)')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('days')
                            ->helperText('Supplier lead time'),
                    ])
                    ->columns(4)
                    ->columnSpan(2)
                    ->collapsible(),

                // Notes and Status Section
                Section::make('Additional Information')
                    ->schema([
                        Checkbox::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive components won\'t appear in BOM selection'),

                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpan(2),
                    ])
                    ->columns(3)
                    ->columnSpan(2)
                    ->collapsible()
                    ->collapsed(),
            ])
            ->columns(2);
    }
}
