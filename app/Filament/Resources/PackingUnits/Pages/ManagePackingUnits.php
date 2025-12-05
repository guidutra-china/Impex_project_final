<?php

namespace App\Filament\Resources\PackingUnits\Pages;

use App\Filament\Resources\PackingUnits\Schemas\ContainerTypeForm;
use App\Filament\Resources\PackingUnits\Schemas\PackingBoxTypeForm;
use App\Filament\Resources\PackingUnits\Tables\ContainerTypesTable;
use App\Filament\Resources\PackingUnits\Tables\PackingBoxTypesTable;
use App\Filament\Resources\PackingUnits\PackingUnitResource;
use App\Models\ContainerType;
use App\Models\PackingBoxType;
use Filament\Actions\CreateAction;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ManagePackingUnits extends ListRecords
{
    protected static string $resource = PackingUnitResource::class;

    public function getTabs(): array
    {
        return [
            'containers' => Tab::make('Containers')
                ->icon('heroicon-o-cube')
                ->badge(ContainerType::where('is_active', true)->count())
                ->modifyQueryUsing(fn ($query) => $query->where('category', 'container')),

            'boxes' => Tab::make('Boxes & Pallets')
                ->icon('heroicon-o-archive-box')
                ->badge(PackingBoxType::where('is_active', true)->count()),
        ];
    }

    public function getDefaultActiveTab(): string
    {
        return 'containers';
    }

    public function table(Table $table): Table
    {
        $activeTab = $this->activeTab ?? 'containers';

        if ($activeTab === 'containers') {
            return ContainerTypesTable::configure($table);
        }

        // For boxes tab, we need to modify the query to use PackingBoxType model
        return $table
            ->query(PackingBoxType::query())
            ->columns(PackingBoxTypesTable::configure($table)->getColumns())
            ->filters(PackingBoxTypesTable::configure($table)->getFilters())
            ->recordActions(PackingBoxTypesTable::configure($table)->getRecordActions())
            ->toolbarActions(PackingBoxTypesTable::configure($table)->getToolbarActions())
            ->defaultSort('name', 'asc')
            ->emptyStateHeading('No packing box types')
            ->emptyStateDescription('Create your first packing box type.')
            ->emptyStateIcon('heroicon-o-archive-box');
    }

    protected function getHeaderActions(): array
    {
        $activeTab = $this->activeTab ?? 'containers';

        if ($activeTab === 'containers') {
            return [
                CreateAction::make('create_container')
                    ->label('New Container Type')
                    ->icon('heroicon-o-plus')
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
            ];
        }

        return [
            CreateAction::make('create_box')
                ->label('New Box/Pallet Type')
                ->icon('heroicon-o-plus')
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
