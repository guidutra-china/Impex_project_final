<?php

namespace App\Filament\Resources\Shipments\Pages;

use App\Filament\Resources\Shipments\ShipmentResource;
use App\Models\Shipment;
use App\Models\ShipmentContainer;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ViewContainerItems extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string $resource = ShipmentResource::class;

    protected static string $view = 'filament.resources.shipments.pages.view-container-items';

    protected static ?string $title = 'Container Items';

    protected static ?string $navigationLabel = 'Container Items';

    public ?array $data = [];
    
    public $selectedContainerId = null;

    public function mount(): void
    {
        // Select first container by default
        $firstContainer = $this->record->containers()->first();
        if ($firstContainer) {
            $this->selectedContainerId = $firstContainer->id;
        }
        
        $this->form->fill([
            'container_id' => $this->selectedContainerId,
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('container_id')
                ->label('Select Container')
                ->options(function () {
                    return $this->record->containers()
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
                        });
                })
                ->searchable()
                ->reactive()
                ->afterStateUpdated(function ($state) {
                    $this->selectedContainerId = $state;
                })
                ->columnSpanFull(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->heading($this->getTableHeading())
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

    protected function getTableQuery(): Builder
    {
        if (!$this->selectedContainerId) {
            return \App\Models\ShipmentContainerItem::query()->whereRaw('1 = 0');
        }

        return \App\Models\ShipmentContainerItem::query()
            ->where('shipment_container_id', $this->selectedContainerId)
            ->with(['product', 'proformaInvoiceItem']);
    }

    protected function getTableHeading(): ?string
    {
        if (!$this->selectedContainerId) {
            return 'No containers available';
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
}
