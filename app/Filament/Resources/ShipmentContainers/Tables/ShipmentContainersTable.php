<?php

namespace App\Filament\Resources\ShipmentContainers\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ShipmentContainersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('container_number')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('container_type')
                    ->badge(),

                BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'draft',
                        'info' => 'packed',
                        'success' => 'sealed',
                        'warning' => 'in_transit',
                        'success' => 'delivered',
                    ]),

                TextColumn::make('current_weight')
                    ->label('Weight')
                    ->sortable(),

                TextColumn::make('current_volume')
                    ->label('Volume')
                    ->sortable(),

                TextColumn::make('shipment.shipment_number')
                    ->label('Shipment')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }
}
