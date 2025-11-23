<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Product;
use Filament\Schemas\Components\ImageEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\TextEntry;
use Filament\Schemas\Schema;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Basic Information Section
                Section::make('Basic Information')
                    ->schema([
                        ImageEntry::make('avatar')
                            ->label('Product Avatar')
                            ->height(150)
                            ->defaultImageUrl(url('/images/no-image.png'))
                            ->columnSpan(2),

                        TextEntry::make('category.name')
                            ->label('Category')
                            ->badge()
                            ->color(fn ($record) => $record->category?->color ?? 'gray')
                            ->icon(fn ($record) => $record->category?->icon),

                        TextEntry::make('name')
                            ->label('Product Name')
                            ->weight('bold')
                            ->size('lg'),

                        TextEntry::make('sku')
                            ->label('SKU')
                            ->badge()
                            ->color('gray')
                            ->copyable(),

                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'success',
                                'inactive' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                        TextEntry::make('currency.code')
                            ->label('Currency')
                            ->formatStateUsing(fn ($record) => $record->currency ? "{$record->currency->code} ({$record->currency->symbol})" : '-'),

                        TextEntry::make('price')
                            ->label('Current Price')
                            ->money(fn ($record) => $record->currency?->code ?? 'USD', divideBy: 100)
                            ->default('-'),

                        TextEntry::make('brand')
                            ->label('Family')
                            ->default('-'),

                        TextEntry::make('model_number')
                            ->label('Model Number')
                            ->default('-'),

                        TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime()
                            ->since(),

                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime()
                            ->since(),
                    ])
                    ->columns(2)
                    ->collapsible(),

                // Supplier & Customer Information
                Section::make('Supplier & Customer Information')
                    ->schema([
                        TextEntry::make('supplier.name')
                            ->label('Supplier')
                            ->default('-'),

                        TextEntry::make('supplier_code')
                            ->label('Supplier Product Code')
                            ->copyable()
                            ->default('-'),

                        TextEntry::make('client.name')
                            ->label('Customer')
                            ->default('-'),

                        TextEntry::make('customer_code')
                            ->label('Customer Product Code')
                            ->copyable()
                            ->default('-'),

                        TextEntry::make('description')
                            ->label('Customer Description')
                            ->columnSpan(2)
                            ->default('-'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),

                // International Trade & Compliance
                Section::make('International Trade & Compliance')
                    ->schema([
                        TextEntry::make('hs_code')
                            ->label('HS Code')
                            ->copyable()
                            ->default('-'),

                        TextEntry::make('origin_country')
                            ->label('Country of Origin')
                            ->default('-'),

                        TextEntry::make('moq')
                            ->label('MOQ')
                            ->formatStateUsing(fn ($state, $record) => $state ? "{$state} {$record->moq_unit}" : '-'),

                        TextEntry::make('lead_time_days')
                            ->label('Lead Time')
                            ->suffix(' days')
                            ->default('-'),

                        TextEntry::make('certifications')
                            ->label('Certifications')
                            ->columnSpan(2)
                            ->default('-'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),

                // Product Dimensions & Weight
                Section::make('Product Dimensions & Weight')
                    ->schema([
                        TextEntry::make('product_length')
                            ->label('Length')
                            ->suffix(' cm')
                            ->default('-'),

                        TextEntry::make('product_width')
                            ->label('Width')
                            ->suffix(' cm')
                            ->default('-'),

                        TextEntry::make('product_height')
                            ->label('Height')
                            ->suffix(' cm')
                            ->default('-'),

                        TextEntry::make('net_weight')
                            ->label('Net Weight')
                            ->suffix(' kg')
                            ->default('-'),

                        TextEntry::make('gross_weight')
                            ->label('Gross Weight')
                            ->suffix(' kg')
                            ->default('-'),
                    ])
                    ->columns(5)
                    ->collapsible()
                    ->collapsed(),

                // Inner Box Packing
                Section::make('Inner Box Packing')
                    ->schema([
                        TextEntry::make('pcs_per_inner_box')
                            ->label('Pieces per Inner Box')
                            ->default('-'),

                        TextEntry::make('inner_box_length')
                            ->label('Length')
                            ->suffix(' cm')
                            ->default('-'),

                        TextEntry::make('inner_box_width')
                            ->label('Width')
                            ->suffix(' cm')
                            ->default('-'),

                        TextEntry::make('inner_box_height')
                            ->label('Height')
                            ->suffix(' cm')
                            ->default('-'),

                        TextEntry::make('inner_box_weight')
                            ->label('Weight')
                            ->suffix(' kg')
                            ->default('-'),
                    ])
                    ->columns(5)
                    ->collapsible()
                    ->collapsed(),

                // Master Carton Packing
                Section::make('Master Carton Packing')
                    ->schema([
                        TextEntry::make('pcs_per_carton')
                            ->label('Pieces per Carton')
                            ->default('-'),

                        TextEntry::make('inner_boxes_per_carton')
                            ->label('Inner Boxes per Carton')
                            ->default('-'),

                        TextEntry::make('carton_length')
                            ->label('Length')
                            ->suffix(' cm')
                            ->default('-'),

                        TextEntry::make('carton_width')
                            ->label('Width')
                            ->suffix(' cm')
                            ->default('-'),

                        TextEntry::make('carton_height')
                            ->label('Height')
                            ->suffix(' cm')
                            ->default('-'),

                        TextEntry::make('carton_weight')
                            ->label('Weight')
                            ->suffix(' kg')
                            ->default('-'),

                        TextEntry::make('carton_cbm')
                            ->label('CBM')
                            ->suffix(' m³')
                            ->default('-'),
                    ])
                    ->columns(4)
                    ->collapsible()
                    ->collapsed(),

                // Container Loading
                Section::make('Container Loading')
                    ->schema([
                        TextEntry::make('cartons_per_20ft')
                            ->label('20ft Container')
                            ->suffix(' cartons')
                            ->default('-'),

                        TextEntry::make('cartons_per_40ft')
                            ->label('40ft Container')
                            ->suffix(' cartons')
                            ->default('-'),

                        TextEntry::make('cartons_per_40hq')
                            ->label('40ft HQ Container')
                            ->suffix(' cartons')
                            ->default('-'),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),

                // Manufacturing Cost Summary
                Section::make('Manufacturing Cost Summary')
                    ->schema([
                        TextEntry::make('bom_material_cost')
                            ->label('BOM Material Cost')
                            ->money(fn ($record) => $record->currency?->code ?? 'USD', divideBy: 100)
                            ->default('-'),

                        TextEntry::make('direct_labor_cost')
                            ->label('Direct Labor Cost')
                            ->money(fn ($record) => $record->currency?->code ?? 'USD', divideBy: 100)
                            ->default('-'),

                        TextEntry::make('direct_overhead_cost')
                            ->label('Direct Overhead Cost')
                            ->money(fn ($record) => $record->currency?->code ?? 'USD', divideBy: 100)
                            ->default('-'),

                        TextEntry::make('total_manufacturing_cost')
                            ->label('Total Manufacturing Cost')
                            ->money(fn ($record) => $record->currency?->code ?? 'USD', divideBy: 100)
                            ->weight('bold')
                            ->color('success')
                            ->default('-'),

                        TextEntry::make('markup_percentage')
                            ->label('Markup Percentage')
                            ->suffix('%')
                            ->default('-'),

                        TextEntry::make('calculated_selling_price')
                            ->label('Calculated Selling Price')
                            ->money(fn ($record) => $record->currency?->code ?? 'USD', divideBy: 100)
                            ->weight('bold')
                            ->color('success')
                            ->default('-'),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),

                // Notes
                Section::make('Notes')
                    ->schema([
                        TextEntry::make('packing_notes')
                            ->label('Packing Notes')
                            ->columnSpan(2)
                            ->default('-'),

                        TextEntry::make('internal_notes')
                            ->label('Internal Notes')
                            ->columnSpan(2)
                            ->default('-'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
