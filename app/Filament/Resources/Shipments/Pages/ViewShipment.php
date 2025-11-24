<?php

namespace App\Filament\Resources\Shipments\Pages;

use App\Filament\Resources\Shipments\ShipmentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Schema;

class ViewShipment extends ViewRecord
{
    protected static string $resource = ShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Shipment Overview')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                Placeholder::make('shipment_number')
                                    ->label('Shipment #')
                                    ->content(fn ($record) => $record->shipment_number),

                                Placeholder::make('shipment_type')
                                    ->label('Type')
                                    ->content(fn ($record) => ucfirst($record->shipment_type)),

                                Placeholder::make('status')
                                    ->label('Status')
                                    ->content(fn ($record) => str_replace('_', ' ', ucwords($record->status, '_'))),

                                Placeholder::make('packing_status')
                                    ->label('Packing Status')
                                    ->content(fn ($record) => match($record->packing_status) {
                                        'not_packed' => 'Not Packed',
                                        'partially_packed' => 'Partially Packed',
                                        'fully_packed' => 'Fully Packed',
                                        default => 'N/A',
                                    }),
                            ]),
                    ]),

                Section::make('Shipping Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Placeholder::make('shipping_method')
                                    ->label('Shipping Method')
                                    ->content(fn ($record) => $record->shipping_method ? ucfirst($record->shipping_method) : '-'),

                                Placeholder::make('carrier')
                                    ->label('Carrier')
                                    ->content(fn ($record) => $record->carrier ?? '-'),

                                Placeholder::make('tracking_number')
                                    ->label('Tracking Number')
                                    ->content(fn ($record) => $record->tracking_number ?? '-'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                Placeholder::make('container_number')
                                    ->label('Container Number')
                                    ->content(fn ($record) => $record->container_number ?? '-'),

                                Placeholder::make('vessel_name')
                                    ->label('Vessel/Flight')
                                    ->content(fn ($record) => $record->vessel_name ?? '-'),

                                Placeholder::make('voyage_number')
                                    ->label('Voyage/Flight #')
                                    ->content(fn ($record) => $record->voyage_number ?? '-'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Placeholder::make('origin_address')
                                    ->label('Origin Address')
                                    ->content(fn ($record) => $record->origin_address ?? '-'),

                                Placeholder::make('destination_address')
                                    ->label('Destination Address')
                                    ->content(fn ($record) => $record->destination_address ?? '-'),
                            ]),
                    ])
                    ->columns(1),

                Section::make('Dates')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Placeholder::make('shipment_date')
                                    ->label('Shipment Date')
                                    ->content(fn ($record) => $record->shipment_date ? $record->shipment_date->format('Y-m-d') : '-'),

                                Placeholder::make('estimated_departure_date')
                                    ->label('Est. Departure')
                                    ->content(fn ($record) => $record->estimated_departure_date ? $record->estimated_departure_date->format('Y-m-d') : '-'),

                                Placeholder::make('estimated_arrival_date')
                                    ->label('Est. Arrival')
                                    ->content(fn ($record) => $record->estimated_arrival_date ? $record->estimated_arrival_date->format('Y-m-d') : '-'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                Placeholder::make('actual_departure_date')
                                    ->label('Actual Departure')
                                    ->content(fn ($record) => $record->actual_departure_date ? $record->actual_departure_date->format('Y-m-d') : '-'),

                                Placeholder::make('actual_arrival_date')
                                    ->label('Actual Arrival')
                                    ->content(fn ($record) => $record->actual_arrival_date ? $record->actual_arrival_date->format('Y-m-d') : '-'),

                                Placeholder::make('actual_delivery_date')
                                    ->label('Actual Delivery')
                                    ->content(fn ($record) => $record->actual_delivery_date ? $record->actual_delivery_date->format('Y-m-d') : '-'),
                            ]),
                    ]),

                Section::make('Measurements & Totals')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                Placeholder::make('total_items')
                                    ->label('Total Items')
                                    ->content(fn ($record) => $record->total_items ?? 0),

                                Placeholder::make('total_quantity')
                                    ->label('Total Quantity')
                                    ->content(fn ($record) => $record->total_quantity ?? 0),

                                Placeholder::make('total_boxes')
                                    ->label('Total Boxes')
                                    ->content(fn ($record) => $record->total_boxes ?? 0),

                                Placeholder::make('total_weight')
                                    ->label('Total Weight')
                                    ->content(fn ($record) => $record->total_weight ? number_format($record->total_weight, 2) . ' kg' : '0.00 kg'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Placeholder::make('total_volume')
                                    ->label('Total Volume')
                                    ->content(fn ($record) => $record->total_volume ? number_format($record->total_volume, 3) . ' m³' : '0.000 m³'),

                                Placeholder::make('total_customs_value')
                                    ->label('Total Customs Value')
                                    ->content(fn ($record) => $record->total_customs_value ? '$' . number_format($record->total_customs_value / 100, 2) : '$0.00'),
                            ]),
                    ]),

                Section::make('Financial')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                Placeholder::make('shipping_cost')
                                    ->label('Shipping Cost')
                                    ->content(fn ($record) => $record->shipping_cost ? '$' . number_format($record->shipping_cost, 2) : '$0.00'),

                                Placeholder::make('insurance_cost')
                                    ->label('Insurance Cost')
                                    ->content(fn ($record) => $record->insurance_cost ? '$' . number_format($record->insurance_cost, 2) : '$0.00'),

                                Placeholder::make('currency.code')
                                    ->label('Currency')
                                    ->content(fn ($record) => $record->currency?->code ?? '-'),

                                Placeholder::make('incoterm')
                                    ->label('Incoterm')
                                    ->content(fn ($record) => $record->incoterm ?? '-'),
                            ]),

                        Placeholder::make('payment_terms')
                            ->label('Payment Terms')
                            ->content(fn ($record) => $record->payment_terms ?? '-'),
                    ]),

                Section::make('Additional Information')
                    ->schema([
                        Placeholder::make('notes')
                            ->label('Internal Notes')
                            ->content(fn ($record) => $record->notes ?? '-')
                            ->columnSpanFull(),

                        Placeholder::make('special_instructions')
                            ->label('Special Instructions')
                            ->content(fn ($record) => $record->special_instructions ?? '-')
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
                                    ->content(fn ($record) => $record->confirmed_at ? $record->confirmed_at->format('Y-m-d H:i:s') : 'Not confirmed'),

                                Placeholder::make('confirmed_by')
                                    ->label('Confirmed By')
                                    ->content(fn ($record) => $record->confirmedBy?->name ?? 'N/A'),
                            ]),
                    ])
                    ->visible(fn ($record) => $record->confirmed_at),

                Section::make('Audit Trail')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Placeholder::make('created_at')
                                    ->label('Created')
                                    ->content(fn ($record) => $record->created_at->format('Y-m-d H:i:s')),

                                Placeholder::make('updated_at')
                                    ->label('Updated')
                                    ->content(fn ($record) => $record->updated_at->format('Y-m-d H:i:s')),

                                Placeholder::make('deleted_at')
                                    ->label('Deleted')
                                    ->content(fn ($record) => $record->deleted_at ? $record->deleted_at->format('Y-m-d H:i:s') : '-')
                                    ->visible(fn ($record) => $record->deleted_at),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
