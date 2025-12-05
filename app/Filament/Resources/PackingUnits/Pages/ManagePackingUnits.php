<?php

namespace App\Filament\Resources\PackingUnits\Pages;

use App\Filament\Resources\PackingUnits\PackingUnitResource;
use App\Filament\Resources\PackingUnits\Schemas\ContainerTypeForm;
use App\Filament\Resources\PackingUnits\Schemas\PackingBoxTypeForm;
use App\Filament\Resources\PackingUnits\Tables\ContainerTypesTable;
use App\Filament\Resources\PackingUnits\Tables\PackingBoxTypesTable;
use App\Models\ContainerType;
use App\Models\PackingBoxType;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Schema;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ManagePackingUnits extends ManageRecords
{
    protected static string $resource = PackingUnitResource::class;

    public function form(Schema $schema): Schema
    {
        // Default to container form, will be dynamic in modal
        return ContainerTypeForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                // Combine both models into a unified query
                // We'll use ContainerType as base and add a type indicator
                return ContainerType::query()
                    ->selectRaw("'container' as packing_type, container_types.*")
                    ->union(
                        PackingBoxType::query()
                            ->selectRaw("'box' as packing_type, packing_box_types.*")
                    );
            })
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('packing_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'container' ? 'Container' : 'Box/Pallet')
                    ->color(fn ($state) => $state === 'container' ? 'primary' : 'success')
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                \Filament\Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->badge(),

                \Filament\Tables\Columns\TextColumn::make('dimensions')
                    ->label('Dimensions (L×W×H)')
                    ->getStateUsing(function ($record) {
                        if ($record->packing_type === 'container') {
                            return sprintf('%.2f × %.2f × %.2f m', $record->length, $record->width, $record->height);
                        }
                        return sprintf('%.1f × %.1f × %.1f cm', $record->length, $record->width, $record->height);
                    }),

                \Filament\Tables\Columns\TextColumn::make('max_volume')
                    ->label('Volume')
                    ->formatStateUsing(fn ($state) => number_format($state, 4) . ' m³')
                    ->sortable()
                    ->alignEnd(),

                \Filament\Tables\Columns\TextColumn::make('max_weight')
                    ->label('Max Weight')
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . ' kg')
                    ->sortable()
                    ->alignEnd(),

                \Filament\Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable()
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('packing_type')
                    ->label('Type')
                    ->options([
                        'container' => 'Containers',
                        'box' => 'Boxes & Pallets',
                    ])
                    ->default('container'),
            ])
            ->defaultSort('name', 'asc');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make('create_container')
                ->label('New Container')
                ->icon('heroicon-o-cube')
                ->color('primary')
                ->model(ContainerType::class)
                ->form(fn (Schema $schema) => ContainerTypeForm::configure($schema))
                ->mutateFormDataUsing(function (array $data): array {
                    $data['created_by'] = auth()->id();
                    $data['category'] = 'container';
                    $data['unit_system'] = 'metric';
                    
                    if (isset($data['base_cost']) && $data['base_cost'] > 0) {
                        $data['base_cost'] = (int) ($data['base_cost'] * 100);
                    }
                    
                    return $data;
                }),

            CreateAction::make('create_box')
                ->label('New Box/Pallet')
                ->icon('heroicon-o-archive-box')
                ->color('success')
                ->model(PackingBoxType::class)
                ->form(fn (Schema $schema) => PackingBoxTypeForm::configure($schema))
                ->mutateFormDataUsing(function (array $data): array {
                    $data['created_by'] = auth()->id();
                    $data['category'] = $data['category'] ?? 'carton_box';
                    $data['unit_system'] = 'centimeters';
                    
                    if (isset($data['unit_cost']) && $data['unit_cost'] > 0) {
                        $data['unit_cost'] = (int) ($data['unit_cost'] * 100);
                    }
                    
                    return $data;
                }),
        ];
    }
}
