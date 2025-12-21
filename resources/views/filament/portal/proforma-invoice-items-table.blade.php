<div class="overflow-x-auto">
    @php
        $record = $getRecord();
        $items = $record->items ?? collect();
        $currency = $record->currency->symbol ?? '$';
    @endphp

    @if($items->count() > 0)
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300">Product</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300">Description</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-700 dark:text-gray-300">Quantity</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-700 dark:text-gray-300">Unit Price</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-700 dark:text-gray-300">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                    <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ $item->product_name }}</td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $item->notes ?? '-' }}</td>
                        <td class="px-4 py-3 text-right text-gray-900 dark:text-gray-100">{{ $item->quantity }}</td>
                        <td class="px-4 py-3 text-right text-gray-900 dark:text-gray-100">{{ $currency }} {{ number_format($item->unit_price, 2) }}</td>
                        <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-gray-100">{{ $currency }} {{ number_format($item->total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-gray-300 dark:border-gray-600">
                    <td colspan="4" class="px-4 py-3 text-right font-bold text-gray-900 dark:text-gray-100">Total:</td>
                    <td class="px-4 py-3 text-right font-bold text-lg text-gray-900 dark:text-gray-100">{{ $currency }} {{ number_format($record->total, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    @else
        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
            No items found
        </div>
    @endif
</div>
