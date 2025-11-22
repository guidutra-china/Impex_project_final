<?php

namespace App\Filament\Resources\Shipments\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ShipmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('shipment_number')
                    ->required(),
                Select::make('sales_order_id')
                    ->relationship('salesOrder', 'id'),
                Select::make('purchase_order_id')
                    ->relationship('purchaseOrder', 'id'),
                Select::make('shipment_type')
                    ->options(['outgoing' => 'Outgoing', 'incoming' => 'Incoming'])
                    ->default('outgoing')
                    ->required(),
                TextInput::make('carrier'),
                TextInput::make('tracking_number'),
                TextInput::make('container_number'),
                Select::make('shipping_method')
                    ->options(['air' => 'Air', 'sea' => 'Sea', 'land' => 'Land', 'courier' => 'Courier']),
                Select::make('status')
                    ->options([
            'pending' => 'Pending',
            'preparing' => 'Preparing',
            'ready_to_ship' => 'Ready to ship',
            'picked_up' => 'Picked up',
            'in_transit' => 'In transit',
            'customs_clearance' => 'Customs clearance',
            'out_for_delivery' => 'Out for delivery',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
            'returned' => 'Returned',
        ])
                    ->default('pending')
                    ->required(),
                Textarea::make('origin_address')
                    ->columnSpanFull(),
                Textarea::make('destination_address')
                    ->columnSpanFull(),
                DatePicker::make('shipment_date'),
                DatePicker::make('estimated_delivery_date'),
                DatePicker::make('actual_delivery_date'),
                TextInput::make('shipping_cost')
                    ->required()
                    ->numeric()
                    ->default(0),
                Select::make('currency_id')
                    ->relationship('currency', 'name'),
                TextInput::make('total_weight')
                    ->numeric(),
                TextInput::make('total_volume')
                    ->numeric(),
                Textarea::make('notes')
                    ->columnSpanFull(),
                Textarea::make('special_instructions')
                    ->columnSpanFull(),
                DateTimePicker::make('notification_sent_at'),
                TextInput::make('created_by')
                    ->numeric(),
            ]);
    }
}
