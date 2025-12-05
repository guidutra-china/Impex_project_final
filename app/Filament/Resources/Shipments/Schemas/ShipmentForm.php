<?php

namespace App\Filament\Resources\Shipments\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Schema;

class ShipmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('shipment_number')
                                    ->label('Shipment Number')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->placeholder('Auto-generated'),

                                Select::make('customer_id')
                                    ->label('Customer')
                                    ->relationship('customer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Select::make('shipment_type')
                                    ->label('Type')
                                    ->options([
                                        'outbound' => 'Outbound (Export)',
                                        'inbound' => 'Inbound (Import)',
                                    ])
                                    ->default('outbound')
                                    ->required(),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'preparing' => 'Preparing',
                                        'ready_to_ship' => 'Ready to Ship',
                                        'picked_up' => 'Picked Up',
                                        'on_board' => 'On Board',
                                        'customs_clearance' => 'Customs Clearance',
                                        'out_for_delivery' => 'Out for Delivery',
                                        'delivered' => 'Delivered',
                                        'cancelled' => 'Cancelled',
                                        'returned' => 'Returned',
                                    ])
                                    ->default('pending')
                                    ->required(),

                                TextInput::make('reference_number')
                                    ->label('Reference Number')
                                    ->placeholder('Customer PO or reference'),
                            ]),
                    ]),

                Section::make('Shipping Details')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('shipping_method')
                                    ->label('Shipping Method')
                                    ->options([
                                        'air' => 'Air Freight',
                                        'sea' => 'Sea Freight',
                                        'land' => 'Land Transport',
                                        'courier' => 'Courier/Express',
                                        'rail' => 'Rail',
                                    ])
                                    ->searchable(),

                                TextInput::make('carrier')
                                    ->label('Carrier')
                                    ->placeholder('e.g., DHL, Maersk, FedEx'),

                                TextInput::make('tracking_number')
                                    ->label('Tracking Number'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('vessel_name')
                                    ->label('Vessel/Flight Name'),

                                TextInput::make('voyage_number')
                                    ->label('Voyage/Flight Number'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Textarea::make('origin_address')
                                    ->label('Origin Address')
                                    ->rows(3)
                                    ->placeholder('Shipper address'),

                                Textarea::make('destination_address')
                                    ->label('Destination Address')
                                    ->rows(3)
                                    ->placeholder('Consignee address'),
                            ]),
                    ]),

                Section::make('Dates')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                DatePicker::make('shipment_date')
                                    ->label('Shipment Date')
                                    ->default(now())
                                    ->required(),

                                DatePicker::make('estimated_departure_date')
                                    ->label('Est. Departure Date'),

                                DatePicker::make('estimated_arrival_date')
                                    ->label('Est. Arrival Date'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                DatePicker::make('actual_departure_date')
                                    ->label('Actual Departure Date'),

                                DatePicker::make('actual_arrival_date')
                                    ->label('Actual Arrival Date'),

                                DatePicker::make('actual_delivery_date')
                                    ->label('Actual Delivery Date'),
                            ]),
                    ]),

                Section::make('Financial')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('shipping_cost')
                                    ->label('Shipping Cost')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0),

                                TextInput::make('insurance_cost')
                                    ->label('Insurance Cost')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0),

                                Select::make('currency_id')
                                    ->label('Currency')
                                    ->relationship('currency', 'code')
                                    ->searchable()
                                    ->preload(),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('incoterm')
                                    ->label('Incoterm')
                                    ->placeholder('e.g., FOB, CIF, EXW')
                                    ->helperText('International Commercial Terms'),

                                TextInput::make('payment_terms')
                                    ->label('Payment Terms')
                                    ->placeholder('e.g., Net 30, COD'),
                            ]),
                    ]),

                Section::make('Measurements')
                    ->description('Auto-calculated from items and packing boxes')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                Placeholder::make('total_items')
                                    ->label('Total Items')
                                    ->content(fn ($record) => $record?->total_items ?? 0),

                                Placeholder::make('total_quantity')
                                    ->label('Total Quantity')
                                    ->content(fn ($record) => $record?->total_quantity ?? 0),

                                Placeholder::make('total_weight')
                                    ->label('Total Weight (kg)')
                                    ->content(fn ($record) => $record?->total_weight ? number_format($record->total_weight, 2) : '0.00'),

                                Placeholder::make('total_volume')
                                    ->label('Total Volume (m³)')
                                    ->content(fn ($record) => $record?->total_volume ? number_format($record->total_volume, 6) : '0.000000'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Placeholder::make('total_boxes')
                                    ->label('Total Boxes')
                                    ->content(fn ($record) => $record?->total_boxes ?? 0),

                                Placeholder::make('packing_status')
                                    ->label('Packing Status')
                                    ->content(function ($record) {
                                        if (!$record) return 'N/A';
                                        
                                        $status = match($record->packing_status) {
                                            'not_packed' => '⚪ Not Packed',
                                            'partially_packed' => '🟡 Partially Packed',
                                            'fully_packed' => '🟢 Fully Packed',
                                            default => 'N/A',
                                        };
                                        
                                        return $status;
                                    }),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Additional Information')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Internal Notes')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('special_instructions')
                            ->label('Special Instructions')
                            ->rows(3)
                            ->helperText('Instructions for carrier or warehouse')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Confirmation Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Placeholder::make('confirmed_at')
                                    ->label('Confirmed At')
                                    ->content(fn ($record) => $record?->confirmed_at ? $record->confirmed_at->format('Y-m-d H:i:s') : 'Not confirmed'),

                                Placeholder::make('confirmed_by')
                                    ->label('Confirmed By')
                                    ->content(fn ($record) => $record?->confirmedBy?->name ?? 'N/A'),
                            ]),
                    ])
                    ->visible(fn ($record) => $record?->confirmed_at)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
