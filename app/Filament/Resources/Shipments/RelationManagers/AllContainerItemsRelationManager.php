<?php

namespace App\Filament\Resources\Shipments\RelationManagers;

use App\Models\ShipmentContainer;
use App\Models\ShipmentContainerItem;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AllContainerItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'containers';

    protected static ?string $title = 'Container Items';

    protected static ?string $icon = 'heroicon-o-cube-transparent';

    public $selectedContainerId = null;

    public function mount(): void
    {
        parent::mount();
        
        // Select first container by default
        $firstContainer = $this->getOwnerRecord()->containers()->first();
        if ($firstContainer) {
            $this->selectedContainerId = $firstContainer->id;
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getContainerItemsQuery())
            ->heading($this->getTableHeading())
            ->headerActions([
                \Filament\Tables\Actions\Action::make('selectContainer')
                    ->label('Select Container')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->form([
                        Select::make('container_id')
                            ->label('Container')
                            ->options($this->getContainerOptions())
                            ->default($this->selectedContainerId)
                            ->required()
                            ->searchable()
                            ->reactive(),
                    ])
                    ->action(function (array $data) {
                        $this->selectedContainerId = $data['container_id'];
                        // Force table refresh
                        $this->dispatch('$refresh');
                    })
                    ->modalWidth('md')
                    ->modalSubmitActionLabel('View Container'),
            ])
            ->columns([
                TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                
                TextColumn::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->sortable()
                    ->alignEnd(),
                
                TextColumn::make('unit_weight')
                    ->label('Unit Weight')
                    ->numeric(2)
                    ->suffix(' kg')
                    ->sortable()
                    ->alignEnd(),
                
                TextColumn::make('total_weight')
                    ->label('Total Weight')
                    ->state(fn ($record) => $record->quantity * $record->unit_weight)
                    ->numeric(2)
                    ->suffix(' kg')
                    ->sortable()
                    ->alignEnd(),
                
                TextColumn::make('unit_volume')
                    ->label('Unit Volume')
                    ->numeric(3)
                    ->suffix(' m³')
                    ->sortable()
                    ->alignEnd(),
                
                TextColumn::make('total_volume')
                    ->label('Total Volume')
                    ->state(fn ($record) => $record->quantity * $record->unit_volume)
                    ->numeric(3)
                    ->suffix(' m³')
                    ->sortable()
                    ->alignEnd(),
                
                TextColumn::make('customs_value')
                    ->label('Customs Value')
                    ->money('USD')
                    ->sortable()
                    ->alignEnd(),
                
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'pending',
                        'success' => 'confirmed',
                    ])
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No items in this container')
            ->emptyStateDescription('Use "Pack Selected Items" in the Shipment Items tab to add items to containers.');
    }

    protected function getContainerItemsQuery(): Builder
    {
        if (!$this->selectedContainerId) {
            return ShipmentContainerItem::query()->whereRaw('1 = 0');
        }

        return ShipmentContainerItem::query()
            ->where('shipment_container_id', $this->selectedContainerId)
            ->with(['product', 'proformaInvoiceItem']);
    }

    protected function getContainerOptions(): array
    {
        return $this->getOwnerRecord()->containers()
            ->get()
            ->mapWithKeys(function ($container) {
                return [
                    $container->id => sprintf(
                        '%s (%s) - %.0f kg / %.2f m³ - %s',
                        $container->container_number,
                        $container->containerType->name ?? 'N/A',
                        $container->current_weight ?? 0,
                        $container->current_volume ?? 0,
                        strtoupper($container->status)
                    )
                ];
            })
            ->toArray();
    }

    protected function getTableHeading(): ?string
    {
        if (!$this->selectedContainerId) {
            return 'No containers available - Please create containers first';
        }

        $container = ShipmentContainer::find($this->selectedContainerId);
        if (!$container) {
            return 'Container not found';
        }

        $weightPercent = $container->max_weight > 0 ? (($container->current_weight ?? 0) / $container->max_weight * 100) : 0;
        $volumePercent = $container->max_volume > 0 ? (($container->current_volume ?? 0) / $container->max_volume * 100) : 0;

        return sprintf(
            '%s | Weight: %.2f / %.2f kg (%.1f%%) | Volume: %.3f / %.3f m³ (%.1f%%) | Status: %s',
            $container->container_number,
            $container->current_weight ?? 0,
            $container->max_weight ?? 0,
            $weightPercent,
            $container->current_volume ?? 0,
            $container->max_volume ?? 0,
            $volumePercent,
            strtoupper($container->status)
        );
    }

    // Dummy method to satisfy RelationManager requirements
    public function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form->schema([]);
    }

    // Override to prevent default relationship query
    protected function getTableQuery(): ?Builder
    {
        return $this->getContainerItemsQuery();
    }

    // This is needed to make it work as a relation manager
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }
}
