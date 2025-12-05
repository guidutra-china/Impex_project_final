<?php

namespace App\Filament\Resources\PackingBoxes;

use App\Models\PackingBox;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\EditAction;
use UnitEnum;

class PackingBoxResource extends Resource
{
    protected static ?string $model = PackingBox::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-cube';

    protected static UnitEnum|string|null $navigationGroup = 'Logistics & Shipping';

    protected static ?string $navigationLabel = 'Packing Boxes';

    protected static ?int $navigationSort = 3;

    protected static bool $shouldRegisterNavigation = false; // Hidden from main nav

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('box_number')
                    ->label('Box #')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('box_type')
                    ->label('Type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('shipment.shipment_number')
                    ->label('Shipment')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('total_items')
                    ->label('Items')
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('total_quantity')
                    ->label('Qty')
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('gross_weight')
                    ->label('Weight (kg)')
                    ->numeric(2)
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('volume')
                    ->label('Volume (m³)')
                    ->numeric(4)
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('packing_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'empty' => 'gray',
                        'in_progress' => 'warning',
                        'completed' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->defaultSort('box_number', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PackingBoxItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPackingBoxes::route('/'),
            'edit' => Pages\EditPackingBox::route('/{record}/edit'),
        ];
    }
}
