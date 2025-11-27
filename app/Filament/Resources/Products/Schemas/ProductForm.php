<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\CountryTypeEnum;
use App\Models\Category;
use App\Models\Product;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->columnSpan(3)
                    ->schema([
                        // Main Product Information Section
                        Section::make('Basic Information')
                            ->columns(2)
                            ->columnSpan(2)
                            ->schema([
                                Select::make('category_id')
                                    ->label('Category')
                                    ->relationship('category', 'name', fn($query) => $query->active()->ordered())
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get, ?Product $record) {
                                        // Only auto-populate features when creating a new product
                                        if ($record !== null) {
                                            return;
                                        }

                                        if (!$state) {
                                            return;
                                        }

                                        // Get category with feature templates
                                        $category = Category::with('categoryFeatures')->find($state);

                                        if (!$category || $category->categoryFeatures->isEmpty()) {
                                            return;
                                        }

                                        // Show notification
                                        Notification::make()
                                            ->title('Features loaded')
                                            ->body("Loaded {$category->categoryFeatures->count()} feature templates from {$category->name}")
                                            ->success()
                                            ->send();
                                    })
                                    ->helperText('Select a category to auto-populate feature templates')
                                    ->columnSpan(1),

                                TextInput::make('name')
                                    ->label('Product Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(1),


                                TextInput::make('sku')
                                    ->label('SKU')
//                            ->required()
//                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),

                                Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'active' => 'Active',
                                        'inactive' => 'Inactive',
                                    ])
                                    ->default('active')
                                    ->required(),

                                Select::make('currency_id')
                                    ->label('Currency')
                                    ->relationship('currency', 'code', fn($query) => $query->where('is_active', true))
                                    ->searchable()
                                    ->default('USD')
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} ({$record->symbol}) - {$record->name}")
                                    ->helperText('Select the currency for this product'),

                                TextInput::make('price')
                                    ->label('Current Price')
                                    ->numeric()
                                    ->prefix(fn(Get $get) => \App\Models\Currency::find($get('currency_id'))?->symbol ?? '$')
                                    ->step(0.01)
                                    ->helperText(fn(?Product $record) => $record && $record->bomItems()->count() > 0
                                        ? 'Auto-synced from Calculated Selling Price (product has BOM)'
                                        : 'Manually enter price (product has no BOM)'
                                    )
                                    ->disabled(fn(?Product $record) => $record && $record->bomItems()->count() > 0)
                                    ->dehydrated(fn(?Product $record) => !$record || $record->bomItems()->count() === 0)
                                    ->dehydrateStateUsing(fn($state) => $state ? (int)( $state * 100 ) : null)
                                    ->formatStateUsing(fn($state, ?Product $record) => // If product has BOM, show calculated_selling_price
                                    $record && $record->bomItems()->count() > 0
                                        ? ( $record->calculated_selling_price ? $record->calculated_selling_price / 100 : 0 )
                                        : ( $state ? $state / 100 : null )
                                    )
                                    ->live(),

                                TextInput::make('brand')
                                    ->label('Family')
                                    ->maxLength(255),

                                TextInput::make('model_number')
                                    ->label('Model Number')
                                    ->maxLength(255),
                            ]),


                Section::make('Pictures')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('avatar')
                        ->label('Product Avatar')
                        ->image()
                        ->imageEditor()
                        ->imageEditorAspectRatios([
                            '1:1',
                            '4:3',
                            '16:9',
                        ])
                        ->directory('products/avatars')
                        ->visibility('public')
                        ->maxSize(2048)
                        ->imagePreviewHeight('150')
                        ->panelLayout('compact')
                        ->removeUploadedFileButtonPosition('right')
                        ->uploadButtonPosition('left')
                        ->uploadProgressIndicatorPosition('left')
                        ->helperText('Upload a main image for this product (max 2MB)')
                        ->columnSpan(2),

                        TextEntry::make('created_at')
                            ->label('Created')
                            ->state(fn(?Product $record): ?string => $record?->created_at?->diffForHumans() ?? 'Just now')
                            ->visible(fn(?Product $record) => $record !== null),

                        TextEntry::make('updated_at')
                            ->label('Last Modified')
                            ->state(fn(?Product $record): ?string => $record?->updated_at?->diffForHumans() ?? 'Not modified')
                            ->visible(fn(?Product $record) => $record !== null),

                ])

            ]),

                // Supplier & Customer Relationships Section
                Section::make('Supplier & Customer Information')
                    ->schema([
                        Select::make('supplier_id')
                            ->label('Supplier')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload(),

                        TextInput::make('supplier_code')
                            ->label('Supplier Product Code')
                            ->maxLength(255)
                            ->helperText('Supplier\'s reference code for this product'),

                        Select::make('client_id')
                            ->label('Customer')
                            ->relationship('client', 'name')
                            ->searchable()
                            ->preload(),

                        TextInput::make('customer_code')
                            ->label('Customer Product Code')
                            ->maxLength(255)
                            ->helperText('Customer\'s reference code for this product'),

                        Textarea::make('description')
                            ->label('Customer Description')
                            ->rows(3)
                            ->columnSpan(2),
                    ])
                    ->columns(2)
                    ->columnSpan(['lg' => 3])
                    ->collapsible(),

                // International Trade Information
                Section::make('International Trade & Compliance')
                    ->schema([
                        TextInput::make('hs_code')
                            ->label('HS Code')
                            ->maxLength(255)
                            ->helperText('Harmonized System Code for customs'),

                        Select::make('origin_country')
                            ->label('Country of Origin')
                            ->searchable()
                            ->options(CountryTypeEnum::toArray()),

                        TextInput::make('moq')
                            ->label('MOQ (Minimum Order Quantity)')
                            ->numeric()
                            ->minValue(1),

                        TextInput::make('moq_unit')
                            ->label('MOQ Unit')
                            ->placeholder('e.g., pcs, cartons, sets')
                            ->maxLength(255),

                        TextInput::make('lead_time_days')
                            ->label('Lead Time (Days)')
                            ->numeric()
                            ->suffix('days')
                            ->minValue(0),

                        Textarea::make('certifications')
                            ->label('Certifications')
                            ->placeholder('e.g., CE, FDA, RoHS, ISO9001')
                            ->rows(2)
                            ->columnSpan(1),
                    ])
                    ->columns(3)
                    ->columnSpan(['lg' => 3])
                    ->collapsible(),

                // Product Dimensions & Weight
                Section::make('Product Dimensions & Weight')
                    ->schema([
                        TextInput::make('product_length')
                            ->label('Length (cm)')
                            ->numeric()
                            ->step(0.01)
                            ->suffix('cm'),

                        TextInput::make('product_width')
                            ->label('Width (cm)')
                            ->numeric()
                            ->step(0.01)
                            ->suffix('cm'),

                        TextInput::make('product_height')
                            ->label('Height (cm)')
                            ->numeric()
                            ->step(0.01)
                            ->suffix('cm'),

                        TextInput::make('net_weight')
                            ->label('Net Weight (kg)')
                            ->numeric()
                            ->step(0.001)
                            ->suffix('kg'),

                        TextInput::make('gross_weight')
                            ->label('Gross Weight (kg)')
                            ->numeric()
                            ->step(0.001)
                            ->suffix('kg'),
                    ])
                    ->columns(5)
                    ->columnSpan(['lg' => 3])
                    ->collapsible(),

                // Inner Box Packing
                Section::make('Inner Box Packing')
                    ->schema([
                        TextInput::make('pcs_per_inner_box')
                            ->label('Pieces per Inner Box')
                            ->numeric()
                            ->minValue(1),

                        TextInput::make('inner_box_length')
                            ->label('Length (cm)')
                            ->numeric()
                            ->step(0.01)
                            ->suffix('cm'),

                        TextInput::make('inner_box_width')
                            ->label('Width (cm)')
                            ->numeric()
                            ->step(0.01)
                            ->suffix('cm'),

                        TextInput::make('inner_box_height')
                            ->label('Height (cm)')
                            ->numeric()
                            ->step(0.01)
                            ->suffix('cm'),

                        TextInput::make('inner_box_weight')
                            ->label('Weight (kg)')
                            ->numeric()
                            ->step(0.001)
                            ->suffix('kg'),
                    ])
                    ->columns(5)
                    ->columnSpan(['lg' => 3])
                    ->collapsible()
                    ->collapsed(),

                // Master Carton Packing
                Section::make('Master Carton Packing')
                    ->schema([
                        TextInput::make('pcs_per_carton')
                            ->label('Pieces per Carton')
                            ->numeric()
                            ->minValue(1)
                            ->live(onBlur: true),

                        TextInput::make('inner_boxes_per_carton')
                            ->label('Inner Boxes per Carton')
                            ->numeric()
                            ->minValue(1),

                        TextInput::make('carton_length')
                            ->label('Length (cm)')
                            ->numeric()
                            ->step(0.01)
                            ->suffix('cm')
                            ->live(onBlur: true),

                        TextInput::make('carton_width')
                            ->label('Width (cm)')
                            ->numeric()
                            ->step(0.01)
                            ->suffix('cm')
                            ->live(onBlur: true),

                        TextInput::make('carton_height')
                            ->label('Height (cm)')
                            ->numeric()
                            ->step(0.01)
                            ->suffix('cm')
                            ->live(onBlur: true),

                        TextInput::make('carton_weight')
                            ->label('Gross Weight (kg)')
                            ->numeric()
                            ->step(0.001)
                            ->suffix('kg'),

                        TextInput::make('carton_cbm')
                            ->label('CBM (m³)')
                            ->numeric()
                            ->step(0.0001)
                            ->suffix('m³')
                            ->helperText('Auto-calculated from dimensions')
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(4)
                    ->columnSpan(['lg' => 3])
                    ->collapsible()
                    ->collapsed(),

                // Container Loading Information
                Section::make('Container Loading Capacity')
                    ->schema([
                        TextInput::make('cartons_per_20ft')
                            ->label('Cartons per 20\' Container')
                            ->numeric()
                            ->minValue(0),

                        TextInput::make('cartons_per_40ft')
                            ->label('Cartons per 40\' Container')
                            ->numeric()
                            ->minValue(0),

                        TextInput::make('cartons_per_40hq')
                            ->label('Cartons per 40\' HQ Container')
                            ->numeric()
                            ->minValue(0),
                    ])
                    ->columns(3)
                    ->columnSpan(['lg' => 3])
                    ->collapsible()
                    ->collapsed(),

                // Manufacturing Cost Summary Section
                Section::make('Manufacturing Cost Summary')
                    ->description('BOM material cost is auto-calculated from components')
                    ->schema([
                        TextInput::make('bom_material_cost')
                            ->label('BOM Material Cost')
                            ->prefix(fn (?Product $record) => $record?->currency?->symbol ?? '$')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn (?Product $record) => $record ? number_format($record->bom_material_cost_dollars, 2) : '0.00')
                            ->helperText('Auto-calculated from BOM components'),

                        TextInput::make('direct_labor_cost')
                            ->label('Direct Labor Cost')
                            ->numeric()
                            ->prefix(fn (?Product $record) => $record?->currency?->symbol ?? '$')
                            ->step(0.01)
                            ->default(0)
                            ->helperText('Assembly/manufacturing labor cost')
                            ->dehydrateStateUsing(fn ($state) => $state ? (int) ($state * 100) : 0)
                            ->formatStateUsing(fn ($state) => $state ? $state / 100 : 0)
                            ->live(onBlur: true),

                        TextInput::make('direct_overhead_cost')
                            ->label('Direct Overhead Cost')
                            ->numeric()
                            ->prefix(fn (?Product $record) => $record?->currency?->symbol ?? '$')
                            ->step(0.01)
                            ->default(0)
                            ->helperText('Factory overhead, utilities, etc.')
                            ->dehydrateStateUsing(fn ($state) => $state ? (int) ($state * 100) : 0)
                            ->formatStateUsing(fn ($state) => $state ? $state / 100 : 0)
                            ->live(onBlur: true),

                        TextInput::make('total_manufacturing_cost')
                            ->label('Total Manufacturing Cost')
                            ->prefix(fn (?Product $record) => $record?->currency?->symbol ?? '$')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn (?Product $record) => $record ? number_format($record->total_manufacturing_cost_dollars, 2) : '0.00')
                            ->helperText('BOM + Labor + Overhead'),

                        TextInput::make('markup_percentage')
                            ->label('Markup %')
                            ->numeric()
                            ->suffix('%')
                            ->step(0.1)
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(1000)
                            ->helperText('Profit margin percentage')
                            ->live(onBlur: true),

                        TextInput::make('calculated_selling_price')
                            ->label('Calculated Selling Price')
                            ->prefix(fn (?Product $record) => $record?->currency?->symbol ?? '$')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn (?Product $record) => $record ? number_format($record->calculated_selling_price_dollars, 2) : '0.00')
                            ->helperText('Manufacturing Cost + Markup'),
                    ])
                    ->columns(3)
                    ->columnSpan(['lg' => 3])
                    ->collapsible()
                    ->hidden(fn (?Product $record) => $record === null),

                // Notes Section
                Section::make('Additional Notes')
                    ->schema([
                        Textarea::make('packing_notes')
                            ->label('Packing Notes')
                            ->rows(3)
                            ->placeholder('Special packing instructions or requirements'),

                        Textarea::make('internal_notes')
                            ->label('Internal Notes')
                            ->rows(3)
                            ->placeholder('Internal notes (not visible to customers)'),
                    ])
                    ->columns(2)
                    ->columnSpan(['lg' => 3])
                    ->collapsible()
                    ->collapsed(),
            ])
            ->columns(3);
    }
}
