<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Version Selectors --}}
        <div class="mb-6">
            {{ $this->form }}
        </div>

        @if($version1 && $version2)
            {{-- Version Headers --}}
            <div class="grid grid-cols-2 gap-4">
                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border-2 border-blue-200 dark:border-blue-800 rounded-lg">
                    <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100">
                        {{ $version1->version_display }}
                    </h3>
                    <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $version1->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                            {{ $version1->status === 'draft' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}
                            {{ $version1->status === 'archived' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : '' }}
                        ">
                            {{ ucfirst($version1->status) }}
                        </span>
                    </p>
                    <p class="text-xs text-blue-600 dark:text-blue-400 mt-2">
                        Created: {{ $version1->created_at->format('M d, Y') }}
                    </p>
                </div>

                <div class="p-4 bg-green-50 dark:bg-green-900/20 border-2 border-green-200 dark:border-green-800 rounded-lg">
                    <h3 class="text-lg font-semibold text-green-900 dark:text-green-100">
                        {{ $version2->version_display }}
                    </h3>
                    <p class="text-sm text-green-700 dark:text-green-300 mt-1">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $version2->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                            {{ $version2->status === 'draft' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}
                            {{ $version2->status === 'archived' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : '' }}
                        ">
                            {{ ucfirst($version2->status) }}
                        </span>
                    </p>
                    <p class="text-xs text-green-600 dark:text-green-400 mt-2">
                        Created: {{ $version2->created_at->format('M d, Y') }}
                    </p>
                </div>
            </div>

            {{-- Cost Summary Comparison --}}
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Cost Summary</h3>
                </div>
                <div class="p-4">
                    <table class="min-w-full">
                        <thead>
                            <tr class="text-xs text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                                <th class="text-left pb-2">Cost Type</th>
                                <th class="text-right pb-2 text-blue-600 dark:text-blue-400">{{ $version1->version_display }}</th>
                                <th class="text-right pb-2 text-green-600 dark:text-green-400">{{ $version2->version_display }}</th>
                                <th class="text-right pb-2">Difference</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="py-2">BOM Material Cost</td>
                                <td class="text-right text-blue-600 dark:text-blue-400">${{ number_format($version1->bom_material_cost_dollars, 2) }}</td>
                                <td class="text-right text-green-600 dark:text-green-400">${{ number_format($version2->bom_material_cost_dollars, 2) }}</td>
                                <td class="text-right font-semibold {{ $version2->bom_material_cost_snapshot < $version1->bom_material_cost_snapshot ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $version2->bom_material_cost_snapshot < $version1->bom_material_cost_snapshot ? '↓' : '↑' }}
                                    ${{ number_format(abs($version2->bom_material_cost_dollars - $version1->bom_material_cost_dollars), 2) }}
                                </td>
                            </tr>
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="py-2">Direct Labor Cost</td>
                                <td class="text-right text-blue-600 dark:text-blue-400">${{ number_format($version1->direct_labor_cost_dollars, 2) }}</td>
                                <td class="text-right text-green-600 dark:text-green-400">${{ number_format($version2->direct_labor_cost_dollars, 2) }}</td>
                                <td class="text-right font-semibold {{ $version2->direct_labor_cost_snapshot < $version1->direct_labor_cost_snapshot ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $version2->direct_labor_cost_snapshot < $version1->direct_labor_cost_snapshot ? '↓' : '↑' }}
                                    ${{ number_format(abs($version2->direct_labor_cost_dollars - $version1->direct_labor_cost_dollars), 2) }}
                                </td>
                            </tr>
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="py-2">Direct Overhead Cost</td>
                                <td class="text-right text-blue-600 dark:text-blue-400">${{ number_format($version1->direct_overhead_cost_dollars, 2) }}</td>
                                <td class="text-right text-green-600 dark:text-green-400">${{ number_format($version2->direct_overhead_cost_dollars, 2) }}</td>
                                <td class="text-right font-semibold {{ $version2->direct_overhead_cost_snapshot < $version1->direct_overhead_cost_snapshot ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $version2->direct_overhead_cost_snapshot < $version1->direct_overhead_cost_snapshot ? '↓' : '↑' }}
                                    ${{ number_format(abs($version2->direct_overhead_cost_dollars - $version1->direct_overhead_cost_dollars), 2) }}
                                </td>
                            </tr>
                            <tr class="bg-gray-50 dark:bg-gray-800 font-bold">
                                <td class="py-3">Total Manufacturing Cost</td>
                                <td class="text-right text-blue-600 dark:text-blue-400">${{ number_format($version1->total_manufacturing_cost_dollars, 2) }}</td>
                                <td class="text-right text-green-600 dark:text-green-400">${{ number_format($version2->total_manufacturing_cost_dollars, 2) }}</td>
                                <td class="text-right text-lg {{ $version2->total_manufacturing_cost_snapshot < $version1->total_manufacturing_cost_snapshot ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $version2->total_manufacturing_cost_snapshot < $version1->total_manufacturing_cost_snapshot ? '↓' : '↑' }}
                                    ${{ number_format(abs($version2->total_manufacturing_cost_dollars - $version1->total_manufacturing_cost_dollars), 2) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Component Comparison --}}
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Component Comparison</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                            <tr class="text-xs text-gray-500 dark:text-gray-400">
                                <th class="px-4 py-3 text-left">Component</th>
                                <th class="px-4 py-3 text-right text-blue-600 dark:text-blue-400">V1 Qty</th>
                                <th class="px-4 py-3 text-right text-green-600 dark:text-green-400">V2 Qty</th>
                                <th class="px-4 py-3 text-right text-blue-600 dark:text-blue-400">V1 Cost</th>
                                <th class="px-4 py-3 text-right text-green-600 dark:text-green-400">V2 Cost</th>
                                <th class="px-4 py-3 text-right">Total Diff</th>
                                <th class="px-4 py-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            @foreach($this->getComparisonData() as $row)
                                <tr class="border-b border-gray-100 dark:border-gray-800 {{ $row['quantity_changed'] || $row['cost_changed'] ? 'bg-yellow-50 dark:bg-yellow-900/10' : '' }}">
                                    <td class="px-4 py-3">
                                        <div class="font-medium">{{ $row['component_name'] }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $row['component_code'] }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-right text-blue-600 dark:text-blue-400">
                                        {{ $row['in_v1'] ? number_format($row['v1_quantity'], 2) : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-green-600 dark:text-green-400">
                                        {{ $row['in_v2'] ? number_format($row['v2_quantity'], 2) : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-blue-600 dark:text-blue-400">
                                        {{ $row['in_v1'] ? '$' . number_format($row['v1_unit_cost'] / 100, 2) : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-green-600 dark:text-green-400">
                                        {{ $row['in_v2'] ? '$' . number_format($row['v2_unit_cost'] / 100, 2) : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold">
                                        @if($row['in_v1'] && $row['in_v2'])
                                            @php
                                                $diff = $row['v2_total_cost'] - $row['v1_total_cost'];
                                            @endphp
                                            <span class="{{ $diff < 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                {{ $diff < 0 ? '↓' : '↑' }} ${{ number_format(abs($diff) / 100, 2) }}
                                            </span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if(!$row['in_v1'] && $row['in_v2'])
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                Added
                                            </span>
                                        @elseif($row['in_v1'] && !$row['in_v2'])
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                Removed
                                            </span>
                                        @elseif($row['quantity_changed'] || $row['cost_changed'])
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                                Changed
                                            </span>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-600">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Select Two Versions</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Choose two BOM versions above to compare their components and costs.
                </p>
            </div>
        @endif
    </div>
</x-filament-panels::page>
