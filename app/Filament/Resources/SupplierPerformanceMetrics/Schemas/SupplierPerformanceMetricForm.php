<?php

namespace App\Filament\Resources\SupplierPerformanceMetrics\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class SupplierPerformanceMetricForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('supplier_id')
                    ->relationship('supplier', 'name')
                    ->required(),
                TextInput::make('period_year')
                    ->required()
                    ->numeric(),
                TextInput::make('period_month')
                    ->required()
                    ->numeric(),
                TextInput::make('total_orders')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('on_time_deliveries')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('late_deliveries')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('average_delay_days')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('total_inspections')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('passed_inspections')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('failed_inspections')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('quality_score')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('total_purchase_value')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('total_orders_value')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('average_order_value')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('response_time_hours')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('communication_score')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('overall_score')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                Select::make('rating')
                    ->options([
            'excellent' => 'Excellent',
            'good' => 'Good',
            'average' => 'Average',
            'poor' => 'Poor',
            'unacceptable' => 'Unacceptable',
        ]),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
