<div class="space-y-4">
    {{-- Scenario Information --}}
    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
        <h3 class="text-lg font-semibold mb-2">{{ $scenario->name }}</h3>
        @if($scenario->description)
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $scenario->description }}</p>
        @endif
    </div>

    {{-- Cost Comparison --}}
    <div class="grid grid-cols-2 gap-4">
        <div class="p-4 border-2 border-gray-200 dark:border-gray-700 rounded-lg">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Current Cost</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                ${{ number_format($scenario->product->total_manufacturing_cost / 100, 2) }}
            </p>
        </div>
        <div class="p-4 border-2 {{ $scenario->reducesCost() ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-red-500 bg-red-50 dark:bg-red-900/20' }} rounded-lg">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Scenario Cost</p>
            <p class="text-2xl font-bold {{ $scenario->reducesCost() ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                ${{ number_format($scenario->scenario_total_cost_dollars, 2) }}
            </p>
            <p class="text-sm font-medium mt-1">
                <span class="{{ $scenario->reducesCost() ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $scenario->reducesCost() ? '↓' : '↑' }}
                    ${{ number_format(abs($scenario->cost_difference_dollars), 2) }}
                    ({{ number_format(abs($scenario->cost_difference_percentage), 2) }}%)
                </span>
            </p>
        </div>
    </div>

    {{-- Detailed Breakdown --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">BOM Material</p>
            <p class="text-lg font-bold text-blue-600 dark:text-blue-400">
                ${{ number_format($scenario->scenario_bom_cost_dollars, 2) }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Current: ${{ number_format($scenario->product->bom_material_cost / 100, 2) }}
            </p>
        </div>
        <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Direct Labor</p>
            <p class="text-lg font-bold text-purple-600 dark:text-purple-400">
                ${{ number_format(($scenario->labor_cost_adjustment ?? $scenario->product->direct_labor_cost) / 100, 2) }}
            </p>
            @if($scenario->labor_cost_adjustment)
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Current: ${{ number_format($scenario->product->direct_labor_cost / 100, 2) }}
                </p>
            @endif
        </div>
        <div class="p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Direct Overhead</p>
            <p class="text-lg font-bold text-orange-600 dark:text-orange-400">
                ${{ number_format(($scenario->overhead_cost_adjustment ?? $scenario->product->direct_overhead_cost) / 100, 2) }}
            </p>
            @if($scenario->overhead_cost_adjustment)
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Current: ${{ number_format($scenario->product->direct_overhead_cost / 100, 2) }}
                </p>
            @endif
        </div>
    </div>

    {{-- Component Adjustments --}}
    @if($scenario->component_cost_adjustments)
        <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
            <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Component Cost Adjustments</h4>
            </div>
            <div class="p-4">
                <table class="min-w-full">
                    <thead>
                        <tr class="text-xs text-gray-500 dark:text-gray-400">
                            <th class="text-left pb-2">Component</th>
                            <th class="text-right pb-2">Current Cost</th>
                            <th class="text-right pb-2">Scenario Cost</th>
                            <th class="text-right pb-2">Difference</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        @foreach($scenario->component_cost_adjustments as $componentProductId => $newCost)
                            @php
                                $bomItem = $scenario->product->bomItems->firstWhere('component_product_id', $componentProductId);
                                $currentCost = $bomItem ? $bomItem->unit_cost : 0;
                                $difference = $newCost - $currentCost;
                            @endphp
                            <tr class="border-t border-gray-100 dark:border-gray-800">
                                <td class="py-2">{{ $bomItem->componentProduct->name ?? 'Unknown' }}</td>
                                <td class="text-right">${{ number_format($currentCost / 100, 2) }}</td>
                                <td class="text-right font-semibold">${{ number_format($newCost / 100, 2) }}</td>
                                <td class="text-right {{ $difference < 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $difference < 0 ? '↓' : '↑' }} ${{ number_format(abs($difference) / 100, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Selling Price Impact --}}
    @if($scenario->scenario_selling_price > 0)
        <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Scenario Selling Price</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        With {{ $scenario->markup_adjustment ?? $scenario->product->markup_percentage }}% markup
                    </p>
                </div>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                    ${{ number_format($scenario->scenario_selling_price_dollars, 2) }}
                </p>
            </div>
        </div>
    @endif

    {{-- Metadata --}}
    <div class="text-xs text-gray-500 dark:text-gray-400 border-t border-gray-200 dark:border-gray-700 pt-4">
        <p><span class="font-medium">Created:</span> {{ $scenario->created_at->format('M d, Y H:i') }}</p>
        @if($scenario->createdBy)
            <p><span class="font-medium">Created By:</span> {{ $scenario->createdBy->name }}</p>
        @endif
        <p><span class="font-medium">Last Updated:</span> {{ $scenario->updated_at->format('M d, Y H:i') }}</p>
    </div>
</div>
