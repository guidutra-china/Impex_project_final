<?php

namespace App\Filament\Actions\Shipments;

use Filament\Tables\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Radio;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;

class PackSelectedItemsBulkAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('packSelectedItems')
            ->label('Pack Selected Items')
            ->icon(Heroicon::OutlinedCube)
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Pack Selected Items')
            ->modalWidth('2xl')
            ->form(function ($livewire) {
                $shipment = $livewire->getOwnerRecord();
                
                return [
                    Radio::make('destination_type')
                        ->label('Pack into')
                        ->options([
                            'container' => 'Container',
                            'box' => 'Packing Box/Pallet',
                        ])
                        ->required()
                        ->reactive()
                        ->default('container'),
                    
                    Select::make('container_id')
                        ->label('Select Container')
                        ->options(function () use ($shipment) {
                            return $shipment->containers()
                                ->where('status', '!=', 'sealed')
                                ->pluck('container_number', 'id');
                        })
                        ->searchable()
                        ->visible(fn ($get) => $get('destination_type') === 'container')
                        ->required(fn ($get) => $get('destination_type') === 'container')
                        ->helperText('Create new containers in the Containers tab'),
                    
                    Select::make('box_id')
                        ->label('Select Box/Pallet')
                        ->options(function () use ($shipment) {
                            return $shipment->packingBoxes()
                                ->where('packing_status', '!=', 'sealed')
                                ->get()
                                ->mapWithKeys(fn ($b) => [
                                    $b->id => sprintf('%s - %s', $b->box_label ?? "Box #{$b->box_number}", ucfirst($b->box_type))
                                ]);
                        })
                        ->searchable()
                        ->visible(fn ($get) => $get('destination_type') === 'box')
                        ->required(fn ($get) => $get('destination_type') === 'box')
                        ->helperText('Create new boxes in the Packing Boxes tab'),
                ];
            })
            ->action(function (Collection $records, array $data, $livewire) {
                $destinationType = $data['destination_type'];
                
                if ($destinationType === 'container') {
                    $container = \App\Models\ShipmentContainer::find($data['container_id']);
                    
                    foreach ($records as $item) {
                        $quantityToPack = $item->quantity_to_ship - $item->quantity_packed;
                        
                        if ($quantityToPack > 0) {
                            \App\Models\ShipmentContainerItem::create([
                                'shipment_container_id' => $container->id,
                                'proforma_invoice_item_id' => $item->proforma_invoice_item_id,
                                'product_id' => $item->product_id,
                                'quantity' => $quantityToPack,
                                'unit_weight' => $item->unit_weight,
                                'total_weight' => $quantityToPack * $item->unit_weight,
                                'unit_volume' => $item->unit_volume,
                                'total_volume' => $quantityToPack * $item->unit_volume,
                                'unit_price' => $item->unit_price,
                                'customs_value' => $quantityToPack * $item->unit_price,
                                'hs_code' => $item->hs_code,
                                'country_of_origin' => $item->country_of_origin,
                                'status' => 'draft',
                                'shipment_sequence' => 1,
                            ]);
                            
                            $item->updatePackedQuantity();
                        }
                    }
                    
                    $container->recalculateTotals();
                    
                } else {
                    $box = \App\Models\PackingBox::find($data['box_id']);
                    
                    foreach ($records as $item) {
                        $quantityToPack = $item->quantity_to_ship - $item->quantity_packed;
                        
                        if ($quantityToPack > 0) {
                            \App\Models\PackingBoxItem::create([
                                'packing_box_id' => $box->id,
                                'product_id' => $item->product_id,
                                'quantity' => $quantityToPack,
                                'unit_weight' => $item->unit_weight,
                                'unit_volume' => $item->unit_volume,
                            ]);
                            
                            $item->updatePackedQuantity();
                        }
                    }
                    
                    $box->recalculateTotals();
                }
                
                \Filament\Notifications\Notification::make()
                    ->title('Items packed successfully')
                    ->success()
                    ->send();
            });
    }
}
