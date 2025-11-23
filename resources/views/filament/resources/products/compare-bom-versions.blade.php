<x-filament-panels::page>
    {{-- Version Selectors --}}
    <x-filament::section>
        <x-slot name="heading">
            Select Versions to Compare
        </x-slot>
        <x-slot name="description">
            Choose two BOM versions to compare their components and costs side by side.
        </x-slot>

        {{ $this->form }}
    </x-filament::section>

    @if($version1 && $version2)
        {{-- Version Headers --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center justify-between">
                        <span>{{ $version1->version_display }}</span>
                        <x-filament::badge :color="match($version1->status) {
                            'active' => 'success',
                            'draft' => 'warning',
                            'archived' => 'gray',
                            default => 'gray'
                        }">
                            {{ ucfirst($version1->status) }}
                        </x-filament::badge>
                    </div>
                </x-slot>

                <div class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Created:</span>
                        <span class="font-medium">{{ $version1->created_at->format('M d, Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Components:</span>
                        <span class="font-medium">{{ $version1->bomVersionItems->count() }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Total Cost:</span>
                        <span class="font-bold text-lg text-primary-600">${{ number_format($version1->total_manufacturing_cost_dollars, 2) }}</span>
                    </div>
                    @if($version1->change_notes)
                        <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                            <p class="text-xs text-gray-600 dark:text-gray-400">{{ $version1->change_notes }}</p>
                        </div>
                    @endif
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center justify-between">
                        <span>{{ $version2->version_display }}</span>
                        <x-filament::badge :color="match($version2->status) {
                            'active' => 'success',
                            'draft' => 'warning',
                            'archived' => 'gray',
                            default => 'gray'
                        }">
                            {{ ucfirst($version2->status) }}
                        </x-filament::badge>
                    </div>
                </x-slot>

                <div class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Created:</span>
                        <span class="font-medium">{{ $version2->created_at->format('M d, Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Components:</span>
                        <span class="font-medium">{{ $version2->bomVersionItems->count() }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Total Cost:</span>
                        <span class="font-bold text-lg text-success-600">${{ number_format($version2->total_manufacturing_cost_dollars, 2) }}</span>
                    </div>
                    @if($version2->change_notes)
                        <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                            <p class="text-xs text-gray-600 dark:text-gray-400">{{ $version2->change_notes }}</p>
                        </div>
                    @endif
                </div>
            </x-filament::section>
        </div>

        {{-- Cost Summary --}}
        <x-filament::section class="mt-6">
            <x-slot name="heading">
                Cost Summary Comparison
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Cost Type
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-primary-600 dark:text-primary-400 uppercase tracking-wider">
                                {{ $version1->version_display }}
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-success-600 dark:text-success-400 uppercase tracking-wider">
                                {{ $version2->version_display }}
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Difference
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                BOM Material Cost
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-primary-600 dark:text-primary-400">
                                ${{ number_format($version1->bom_material_cost_dollars, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-success-600 dark:text-success-400">
                                ${{ number_format($version2->bom_material_cost_dollars, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold">
                                @php
                                    $diff = $version2->bom_material_cost_snapshot - $version1->bom_material_cost_snapshot;
                                @endphp
                                <span class="{{ $diff < 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                                    {{ $diff < 0 ? '↓' : '↑' }} ${{ number_format(abs($diff) / 100, 2) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                Direct Labor Cost
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-primary-600 dark:text-primary-400">
                                ${{ number_format($version1->direct_labor_cost_dollars, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-success-600 dark:text-success-400">
                                ${{ number_format($version2->direct_labor_cost_dollars, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold">
                                @php
                                    $diff = $version2->direct_labor_cost_snapshot - $version1->direct_labor_cost_snapshot;
                                @endphp
                                <span class="{{ $diff < 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                                    {{ $diff < 0 ? '↓' : '↑' }} ${{ number_format(abs($diff) / 100, 2) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                Direct Overhead Cost
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-primary-600 dark:text-primary-400">
                                ${{ number_format($version1->direct_overhead_cost_dollars, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-success-600 dark:text-success-400">
                                ${{ number_format($version2->direct_overhead_cost_dollars, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold">
                                @php
                                    $diff = $version2->direct_overhead_cost_snapshot - $version1->direct_overhead_cost_snapshot;
                                @endphp
                                <span class="{{ $diff < 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                                    {{ $diff < 0 ? '↓' : '↑' }} ${{ number_format(abs($diff) / 100, 2) }}
                                </span>
                            </td>
                        </tr>
                        <tr class="bg-gray-50 dark:bg-gray-800">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-gray-100">
                                Total Manufacturing Cost
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-base font-bold text-right text-primary-600 dark:text-primary-400">
                                ${{ number_format($version1->total_manufacturing_cost_dollars, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-base font-bold text-right text-success-600 dark:text-success-400">
                                ${{ number_format($version2->total_manufacturing_cost_dollars, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-base font-bold text-right">
                                @php
                                    $diff = $version2->total_manufacturing_cost_snapshot - $version1->total_manufacturing_cost_snapshot;
                                @endphp
                                <span class="{{ $diff < 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                                    {{ $diff < 0 ? '↓' : '↑' }} ${{ number_format(abs($diff) / 100, 2) }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        {{-- Component Comparison --}}
        <x-filament::section class="mt-6">
            <x-slot name="heading">
                Component Comparison
            </x-slot>
            <x-slot name="description">
                Detailed comparison of components between the two versions. Highlighted rows indicate changes.
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Component
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-primary-600 dark:text-primary-400 uppercase tracking-wider">
                                V1 Qty
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-success-600 dark:text-success-400 uppercase tracking-wider">
                                V2 Qty
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-primary-600 dark:text-primary-400 uppercase tracking-wider">
                                V1 Unit Cost
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-success-600 dark:text-success-400 uppercase tracking-wider">
                                V2 Unit Cost
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Total Diff
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Status
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($this->getComparisonData() as $row)
                            <tr class="{{ $row['quantity_changed'] || $row['cost_changed'] ? 'bg-warning-50 dark:bg-warning-900/10' : '' }}">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $row['component_name'] }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        SKU: {{ $row['component_sku'] ?? '—' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-primary-600 dark:text-primary-400">
                                    {{ $row['in_v1'] ? number_format($row['v1_quantity'], 2) : '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-success-600 dark:text-success-400">
                                    {{ $row['in_v2'] ? number_format($row['v2_quantity'], 2) : '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-primary-600 dark:text-primary-400">
                                    {{ $row['in_v1'] ? '$' . number_format($row['v1_unit_cost'] / 100, 2) : '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-success-600 dark:text-success-400">
                                    {{ $row['in_v2'] ? '$' . number_format($row['v2_unit_cost'] / 100, 2) : '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold">
                                    @if($row['in_v1'] && $row['in_v2'])
                                        @php
                                            $diff = $row['v2_total_cost'] - $row['v1_total_cost'];
                                        @endphp
                                        <span class="{{ $diff < 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                                            {{ $diff < 0 ? '↓' : '↑' }} ${{ number_format(abs($diff) / 100, 2) }}
                                        </span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if(!$row['in_v1'] && $row['in_v2'])
                                        <x-filament::badge color="success">
                                            Added
                                        </x-filament::badge>
                                    @elseif($row['in_v1'] && !$row['in_v2'])
                                        <x-filament::badge color="danger">
                                            Removed
                                        </x-filament::badge>
                                    @elseif($row['quantity_changed'] || $row['cost_changed'])
                                        <x-filament::badge color="warning">
                                            Changed
                                        </x-filament::badge>
                                    @else
                                        <x-filament::badge color="gray">
                                            Unchanged
                                        </x-filament::badge>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @else
        <x-filament::section class="mt-6">
            <div class="text-center py-12">
                <x-filament::icon
                    icon="heroicon-o-arrows-right-left"
                    class="mx-auto h-12 w-12 text-gray-400"
                />
                <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100">
                    No versions selected
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Select two versions above to see a detailed comparison.
                </p>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
