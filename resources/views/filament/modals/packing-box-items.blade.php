<div class="space-y-4">
    @if($box->packingBoxItems->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">SKU</th>
                        <th class="px-4 py-3 text-left font-semibold">Product</th>
                        <th class="px-4 py-3 text-center font-semibold">Quantity</th>
                        <th class="px-4 py-3 text-center font-semibold">Unit Weight</th>
                        <th class="px-4 py-3 text-center font-semibold">Total Weight</th>
                        <th class="px-4 py-3 text-center font-semibold">Unit Volume</th>
                        <th class="px-4 py-3 text-center font-semibold">Total Volume</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($box->packingBoxItems as $item)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-3">{{ $item->shipmentItem->product_sku ?? 'N/A' }}</td>
                            <td class="px-4 py-3">{{ $item->shipmentItem->product_name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-center font-medium">{{ number_format($item->quantity) }}</td>
                            <td class="px-4 py-3 text-center">{{ number_format($item->unit_weight, 2) }} kg</td>
                            <td class="px-4 py-3 text-center font-semibold">{{ number_format($item->quantity * $item->unit_weight, 2) }} kg</td>
                            <td class="px-4 py-3 text-center">{{ number_format($item->unit_volume, 4) }} m³</td>
                            <td class="px-4 py-3 text-center font-semibold">{{ number_format($item->quantity * $item->unit_volume, 4) }} m³</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-100 dark:bg-gray-900 font-bold">
                    <tr>
                        <td colspan="2" class="px-4 py-3 text-right">TOTALS:</td>
                        <td class="px-4 py-3 text-center">{{ number_format($box->total_quantity) }}</td>
                        <td class="px-4 py-3"></td>
                        <td class="px-4 py-3 text-center text-primary-600">{{ number_format($box->net_weight, 2) }} kg</td>
                        <td class="px-4 py-3"></td>
                        <td class="px-4 py-3 text-center text-primary-600">{{ number_format($box->volume, 4) }} m³</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="grid grid-cols-2 gap-4 mt-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Box Type</p>
                <p class="font-semibold">{{ ucfirst($box->box_type) }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Packing Status</p>
                <p class="font-semibold">{{ ucfirst(str_replace('_', ' ', $box->packing_status)) }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Total Items</p>
                <p class="font-semibold">{{ $box->total_items }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Total Quantity</p>
                <p class="font-semibold">{{ number_format($box->total_quantity) }} units</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Net Weight</p>
                <p class="font-semibold text-lg">{{ number_format($box->net_weight, 2) }} kg</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Gross Weight</p>
                <p class="font-semibold text-lg">{{ number_format($box->gross_weight, 2) }} kg</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Volume</p>
                <p class="font-semibold text-lg">{{ number_format($box->volume, 4) }} m³</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Dimensions (L×W×H)</p>
                <p class="font-semibold">{{ $box->length }} × {{ $box->width }} × {{ $box->height }} cm</p>
            </div>
        </div>
    @else
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
            <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100">No items in this box</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">This box is empty. Use the bulk action "Pack Selected Items" to add items.</p>
        </div>
    @endif
</div>
