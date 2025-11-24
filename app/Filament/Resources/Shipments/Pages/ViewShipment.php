<?php

namespace App\Filament\Resources\Shipments\Pages;

use App\Filament\Resources\Shipments\ShipmentResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\BadgeEntry;

class ViewShipment extends ViewRecord
{
    protected static string $resource = ShipmentResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Shipment Overview')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('shipment_number')
                                    ->label('Shipment #')
                                    ->weight('bold')
                                    ->size('lg'),

                                BadgeEntry::make('shipment_type')
                                    ->label('Type')
                                    ->formatStateUsing(fn ($state) => ucfirst($state))
                                    ->colors([
                                        'primary' => 'outbound',
                                        'success' => 'inbound',
                                    ]),

                                BadgeEntry::make('status')
                                    ->label('Status')
                                    ->formatStateUsing(fn ($state) => str_replace('_', ' ', ucwords($state, '_')))
                                    ->colors([
                                        'secondary' => 'draft',
                                        'warning' => ['preparing', 'ready_to_ship'],
                                        'info' => 'confirmed',
                                        'primary' => ['picked_up', 'in_transit'],
                                        'success' => 'delivered',
                                        'danger' => ['cancelled', 'returned'],
                                    ]),

                                BadgeEntry::make('packing_status')
                                    ->label('Packing Status')
                                    ->formatStateUsing(fn ($state) => match($state) {
                                        'not_packed' => 'Not Packed',
                                        'partially_packed' => 'Partially Packed',
                                        'fully_packed' => 'Fully Packed',
                                        default => 'N/A',
                                    })
                                    ->colors([
                                        'secondary' => 'not_packed',
                                        'warning' => 'partially_packed',
                                        'success' => 'fully_packed',
                                    ]),
                            ]),
                    ]),

                Section::make('Shipping Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('shipping_method')
                                    ->label('Shipping Method')
                                    ->formatStateUsing(fn ($state) => ucfirst($state)),

                                TextEntry::make('carrier')
                                    ->label('Carrier'),

                                TextEntry::make('tracking_number')
                                    ->label('Tracking Number')
                                    ->copyable(),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextEntry::make('container_number')
                                    ->label('Container Number'),

                                TextEntry::make('vessel_name')
                                    ->label('Vessel/Flight'),

                                TextEntry::make('voyage_number')
                                    ->label('Voyage/Flight #'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('origin_address')
                                    ->label('Origin Address')
                                    ->markdown(),

                                TextEntry::make('destination_address')
                                    ->label('Destination Address')
                                    ->markdown(),
                            ]),
                    ])
                    ->columns(1),

                Section::make('Dates')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('shipment_date')
                                    ->label('Shipment Date')
                                    ->date('Y-m-d'),

                                TextEntry::make('estimated_departure_date')
                                    ->label('Est. Departure')
                                    ->date('Y-m-d'),

                                TextEntry::make('estimated_arrival_date')
                                    ->label('Est. Arrival')
                                    ->date('Y-m-d'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextEntry::make('actual_departure_date')
                                    ->label('Actual Departure')
                                    ->date('Y-m-d'),

                                TextEntry::make('actual_arrival_date')
                                    ->label('Actual Arrival')
                                    ->date('Y-m-d'),

                                TextEntry::make('actual_delivery_date')
                                    ->label('Actual Delivery')
                                    ->date('Y-m-d'),
                            ]),
                    ]),

                Section::make('Measurements & Totals')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('total_items')
                                    ->label('Total Items')
                                    ->badge()
                                    ->color('primary'),

                                TextEntry::make('total_quantity')
                                    ->label('Total Quantity')
                                    ->badge()
                                    ->color('success'),

                                TextEntry::make('total_boxes')
                                    ->label('Total Boxes')
                                    ->badge()
                                    ->color('info'),

                                TextEntry::make('total_weight')
                                    ->label('Total Weight')
                                    ->formatStateUsing(fn ($state) => number_format($state, 2) . ' kg'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('total_volume')
                                    ->label('Total Volume')
                                    ->formatStateUsing(fn ($state) => number_format($state, 3) . ' m³'),

                                TextEntry::make('total_customs_value')
                                    ->label('Total Customs Value')
                                    ->money('USD', 100),
                            ]),
                    ]),

                Section::make('Financial')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('shipping_cost')
                                    ->label('Shipping Cost')
                                    ->money('USD', 100),

                                TextEntry::make('insurance_cost')
                                    ->label('Insurance Cost')
                                    ->money('USD', 100),

                                TextEntry::make('currency.code')
                                    ->label('Currency'),

                                TextEntry::make('incoterm')
                                    ->label('Incoterm'),
                            ]),

                        TextEntry::make('payment_terms')
                            ->label('Payment Terms'),
                    ]),

                Section::make('Additional Information')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Internal Notes')
                            ->markdown()
                            ->columnSpanFull(),

                        TextEntry::make('special_instructions')
                            ->label('Special Instructions')
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Confirmation Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('confirmed_at')
                                    ->label('Confirmed At')
                                    ->dateTime('Y-m-d H:i:s'),

                                TextEntry::make('confirmedBy.name')
                                    ->label('Confirmed By'),
                            ]),
                    ])
                    ->visible(fn ($record) => $record->confirmed_at),

                Section::make('Audit Trail')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Created')
                                    ->dateTime('Y-m-d H:i:s'),

                                TextEntry::make('updated_at')
                                    ->label('Updated')
                                    ->dateTime('Y-m-d H:i:s'),

                                TextEntry::make('deleted_at')
                                    ->label('Deleted')
                                    ->dateTime('Y-m-d H:i:s')
                                    ->visible(fn ($record) => $record->deleted_at),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
