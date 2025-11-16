<?php

namespace App\Filament\Resources\SupplierQuotes\Products\Pages;

use App\Filament\Resources\SupplierQuotes\Products\ProductResource;
use App\Models\BomVersion;
use App\Models\Product;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Form;

class CompareBomVersions extends Page
{
    protected static string $resource = ProductResource::class;

    protected string $view = 'filament.resources.products.compare-bom-versions';

    protected static ?string $title = 'Compare BOM Versions';

    public ?array $data = [];
    public ?BomVersion $version1 = null;
    public ?BomVersion $version2 = null;
    public ?Product $product = null;

    public function mount(): void
    {
        $this->product = $this->getRecord();
        
        // Get version IDs from query params
        $version1Id = request()->query('version1');
        $version2Id = request()->query('version2');

        if ($version1Id) {
            $this->version1 = BomVersion::with('bomVersionItems.component')->find($version1Id);
        }

        if ($version2Id) {
            $this->version2 = BomVersion::with('bomVersionItems.component')->find($version2Id);
        }

        $this->form->fill([
            'version1_id' => $version1Id,
            'version2_id' => $version2Id,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('version1_id')
                    ->label('Version 1')
                    ->options(function () {
                        return $this->product->bomVersions()
                            ->get()
                            ->mapWithKeys(fn ($v) => [$v->id => "{$v->version_display} ({$v->status})"])
                            ->toArray();
                    })
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->version1 = BomVersion::with('bomVersionItems.component')->find($state);
                    }),

                Select::make('version2_id')
                    ->label('Version 2')
                    ->options(function () {
                        return $this->product->bomVersions()
                            ->get()
                            ->mapWithKeys(fn ($v) => [$v->id => "{$v->version_display} ({$v->status})"])
                            ->toArray();
                    })
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->version2 = BomVersion::with('bomVersionItems.component')->find($state);
                    }),
            ])
            ->statePath('data');
    }

    public function getComparisonData(): array
    {
        if (!$this->version1 || !$this->version2) {
            return [];
        }

        $v1Items = $this->version1->bomVersionItems->keyBy('component_id');
        $v2Items = $this->version2->bomVersionItems->keyBy('component_id');

        $allComponentIds = $v1Items->keys()->merge($v2Items->keys())->unique();

        $comparison = [];

        foreach ($allComponentIds as $componentId) {
            $item1 = $v1Items->get($componentId);
            $item2 = $v2Items->get($componentId);

            $comparison[] = [
                'component_id' => $componentId,
                'component_name' => $item1?->component->name ?? $item2?->component->name,
                'component_code' => $item1?->component->code ?? $item2?->component->code,
                'in_v1' => $item1 !== null,
                'in_v2' => $item2 !== null,
                'v1_quantity' => $item1?->quantity,
                'v2_quantity' => $item2?->quantity,
                'v1_waste' => $item1?->waste_factor,
                'v2_waste' => $item2?->waste_factor,
                'v1_unit_cost' => $item1?->unit_cost_snapshot,
                'v2_unit_cost' => $item2?->unit_cost_snapshot,
                'v1_total_cost' => $item1?->total_cost_snapshot,
                'v2_total_cost' => $item2?->total_cost_snapshot,
                'quantity_changed' => $item1 && $item2 && $item1->quantity != $item2->quantity,
                'cost_changed' => $item1 && $item2 && $item1->unit_cost_snapshot != $item2->unit_cost_snapshot,
            ];
        }

        return $comparison;
    }
}
