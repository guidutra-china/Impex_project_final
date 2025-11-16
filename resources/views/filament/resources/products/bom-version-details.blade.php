<div class="space-y-4">
    {{-- Version Information --}}
    <div class="grid grid-cols-2 gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Version</p>
            <p class="text-lg font-semibold">{{ $version->version_display }}</p>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</p>
            <p class="text-lg">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                    {{ $version->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                    {{ $version->status === 'draft' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}
                    {{ $version->status === 'archived' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : '' }}
                ">
                    {{ ucfirst($version->status) }}
                </span>
            </p>
        </div>
        <div class="col-span-2">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Change Notes</p>
            <p class="text-sm">{{ $version->change_notes ?? '—' }}</p>
        </div>
    </div>

    {{-- Cost Summary --}}
    <div class="grid grid-cols-4 gap-4">
        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">BOM Material</p>
            <p class="text-xl font-bold text-blue-600 dark:text-blue-400">
                ${{ number_format($version->bom_material_cost_dollars, 2) }}
            </p>
        </div>
        <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Direct Labor</p>
            <p class="text-xl font-bold text-purple-600 dark:text-purple-400">
                ${{ number_format($version->direct_labor_cost_dollars, 2) }}
            </p>
        </div>
        <div class="p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Direct Overhead</p>
            <p class="text-xl font-bold text-orange-600 dark:text-orange-400">
                ${{ number_format($version->direct_overhead_cost_dollars, 2) }}
            </p>
        </div>
        <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Cost</p>
            <p class="text-xl font-bold text-green-600 dark:text-green-400">
                ${{ number_format($version->total_manufacturing_cost_dollars, 2) }}
            </p>
        </div>
    </div>

    {{-- BOM Items Table --}}
    <div class="overflow-hidden border border-gray-200 dark:border-gray-700 rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Code
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Component
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Quantity
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Waste %
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Actual Qty
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Unit Cost
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Total Cost
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($version->bomVersionItems as $item)
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                {{ $item->component->code }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $item->component->name }}
                            </div>
                            @if($item->notes)
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $item->notes }}
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                            {{ number_format($item->quantity, 2) }} {{ $item->unit_of_measure }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                            {{ number_format($item->waste_factor, 1) }}%
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                            {{ number_format($item->actual_quantity, 2) }} {{ $item->unit_of_measure }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                            ${{ number_format($item->unit_cost_dollars, 2) }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-semibold text-green-600 dark:text-green-400">
                            ${{ number_format($item->total_cost_dollars, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            No components in this version
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <td colspan="6" class="px-4 py-3 text-right text-sm font-semibold text-gray-900 dark:text-gray-100">
                        BOM Material Cost:
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-bold text-green-600 dark:text-green-400">
                        ${{ number_format($version->bom_material_cost_dollars, 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Metadata --}}
    <div class="grid grid-cols-2 gap-4 text-sm text-gray-500 dark:text-gray-400">
        <div>
            <span class="font-medium">Created:</span> {{ $version->created_at->format('M d, Y H:i') }}
        </div>
        <div>
            <span class="font-medium">Created By:</span> {{ $version->createdBy->name ?? 'System' }}
        </div>
        @if($version->activated_at)
            <div>
                <span class="font-medium">Activated:</span> {{ $version->activated_at->format('M d, Y H:i') }}
            </div>
        @endif
        @if($version->archived_at)
            <div>
                <span class="font-medium">Archived:</span> {{ $version->archived_at->format('M d, Y H:i') }}
            </div>
        @endif
    </div>
</div>
