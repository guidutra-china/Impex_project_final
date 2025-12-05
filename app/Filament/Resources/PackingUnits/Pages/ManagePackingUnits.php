<?php

namespace App\Filament\Resources\PackingUnits\Pages;

use App\Filament\Resources\PackingUnits\PackingUnitResource;
use App\Filament\Resources\PackingUnits\Schemas\ContainerTypeForm;
use App\Filament\Resources\PackingUnits\Schemas\PackingBoxTypeForm;
use App\Filament\Resources\PackingUnits\Tables\ContainerTypesTable;
use App\Filament\Resources\PackingUnits\Tables\PackingBoxTypesTable;
use App\Models\ContainerType;
use App\Models\PackingBoxType;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ManagePackingUnits extends ManageRecords
{
    protected static string $resource = PackingUnitResource::class;

    // Property to track which view we're showing
    public string $currentView = 'containers';

    public function mount(): void
    {
        parent::mount();
        
        // Check if there's a view parameter in the URL
        $this->currentView = request()->query('view', 'containers');
    }

    public function form(Schema $schema): Schema
    {
        if ($this->currentView === 'containers') {
            return ContainerTypeForm::configure($schema);
        }
        
        return PackingBoxTypeForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        if ($this->currentView === 'containers') {
            return ContainerTypesTable::configure($table);
        }
        
        return PackingBoxTypesTable::configure($table)
            ->query(PackingBoxType::query());
    }

    protected function getHeaderActions(): array
    {
        $actions = [];

        // View switcher buttons
        $actions[] = Action::make('view_containers')
            ->label('Containers')
            ->icon('heroicon-o-cube')
            ->color($this->currentView === 'containers' ? 'primary' : 'gray')
            ->badge(ContainerType::where('is_active', true)->count())
            ->url(fn () => static::getUrl(['view' => 'containers']))
            ->outlined($this->currentView !== 'containers');

        $actions[] = Action::make('view_boxes')
            ->label('Boxes & Pallets')
            ->icon('heroicon-o-archive-box')
            ->color($this->currentView === 'boxes' ? 'success' : 'gray')
            ->badge(PackingBoxType::where('is_active', true)->count())
            ->url(fn () => static::getUrl(['view' => 'boxes']))
            ->outlined($this->currentView !== 'boxes');

        // Create button based on current view
        if ($this->currentView === 'containers') {
            $actions[] = CreateAction::make()
                ->label('New Container')
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
                });
        } else {
            $actions[] = CreateAction::make()
                ->label('New Box/Pallet')
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
                });
        }

        return $actions;
    }
}
