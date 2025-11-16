<x-filament-panels::page>
    @if($order)
        {{-- Order Summary --}}
        <div class="mb-6">
            <x-filament::section>
                <x-slot name="heading">
                    Order Summary
                </x-slot>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Order Number</div>
                        <div class="text-lg font-semibold">{{ $order->order_number }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Customer</div>
                        <div class="text-lg font-semibold">{{ $order->customer->name }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Currency</div>
                        <div class="text-lg font-semibold">{{ $order->currency->code ?? 'USD' }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Commission</div>
                        <div class="text-lg font-semibold">{{ $order->commission_percent }}% ({{ ucfirst($order->commission_type) }})</div>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Total Quotes</div>
                        <div class="text-lg font-semibold">{{ $summary['total_quotes'] ?? 0 }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Draft</div>
                        <div class="text-lg">{{ $summary['draft_quotes'] ?? 0 }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Sent</div>
                        <div class="text-lg">{{ $summary['sent_quotes'] ?? 0 }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Accepted</div>
                        <div class="text-lg">{{ $summary['accepted_quotes'] ?? 0 }}</div>
                    </div>
                </div>
            </x-filament::section>
        </div>

        @if(isset($comparison['overall']))
            {{-- Overall Comparison --}}
            <div class="mb-6">
                <x-filament::section>
                    <x-slot name="heading">
                        Overall Comparison
                    </x-slot>

                    <div class="overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-800">
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Supplier</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Currency</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Original Total</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Converted Total</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Exchange Rate</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($comparison['overall']['all_quotes'] as $quote)
                                    <tr class="{{ $quote['quote_id'] == $comparison['overall']['cheapest_quote_id'] ? 'bg-green-50 dark:bg-green-900/20' : '' }}">
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $quote['supplier'] }}
                                                </div>
                                                @if($quote['quote_id'] == $comparison['overall']['cheapest_quote_id'])
                                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                                        Cheapest
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                            {{ $quote['currency'] }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right text-sm text-gray-900 dark:text-gray-100">
                                            {{ $quote['currency'] }}{{ number_format($quote['total_after_commission'] / 100, 2) }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $quote['order_currency'] }}{{ number_format($quote['total_after_commission'] / 100, 2) }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-center text-sm text-gray-500 dark:text-gray-400">
                                            {{ $quote['exchange_rate'] ? number_format($quote['exchange_rate'], 4) : '1.0000' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                @if($quote['status'] == 'accepted') bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100
                                                @elseif($quote['status'] == 'sent') bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100
                                                @elseif($quote['status'] == 'rejected') bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100
                                                @else bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100
                                                @endif">
                                                {{ ucfirst($quote['status']) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($comparison['overall']['savings'] > 0)
                        <div class="mt-4 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                            <div class="flex items-center">
                                <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-green-800 dark:text-green-200">
                                        Potential Savings: {{ $order->currency->symbol }}{{ number_format($comparison['overall']['savings'] / 100, 2) }}
                                        ({{ number_format($comparison['overall']['savings_percent'], 2) }}%)
                                    </h3>
                                    <div class="mt-1 text-sm text-green-700 dark:text-green-300">
                                        By choosing {{ $comparison['overall']['cheapest_supplier'] }} over the most expensive option
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </x-filament::section>
            </div>

            {{-- Product-by-Product Comparison --}}
            <div class="mb-6">
                <x-filament::section>
                    <x-slot name="heading">
                        Product-by-Product Comparison
                    </x-slot>

                    @foreach($comparison['by_product'] as $productComparison)
                        <div class="mb-6 last:mb-0">
                            <h3 class="text-lg font-semibold mb-3 text-gray-900 dark:text-gray-100">
                                {{ $productComparison['product'] }} ({{ $productComparison['product_code'] }})
                                <span class="text-sm font-normal text-gray-500 dark:text-gray-400">- Quantity: {{ $productComparison['quantity'] }}</span>
                            </h3>

                            <div class="overflow-x-auto">
                                <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead>
                                        <tr class="bg-gray-50 dark:bg-gray-800">
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Supplier</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Unit Price</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total Price</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Converted</th>
                                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($productComparison['all_prices'] as $price)
                                            <tr class="{{ isset($productComparison['cheapest']) && $price['supplier_id'] == $productComparison['cheapest']['supplier_id'] ? 'bg-green-50 dark:bg-green-900/20' : '' }}">
                                                <td class="px-4 py-2 whitespace-nowrap text-sm">
                                                    <div class="flex items-center">
                                                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ $price['supplier'] }}</span>
                                                        @if(isset($productComparison['cheapest']) && $price['supplier_id'] == $productComparison['cheapest']['supplier_id'])
                                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                                                Best
                                                            </span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="px-4 py-2 whitespace-nowrap text-right text-sm text-gray-900 dark:text-gray-100">
                                                    @if($price['status'] == 'Quoted')
                                                        {{ $price['currency'] }}{{ number_format($price['price'] / 100, 2) }}
                                                    @else
                                                        <span class="text-gray-400">-</span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-2 whitespace-nowrap text-right text-sm text-gray-900 dark:text-gray-100">
                                                    @if($price['status'] == 'Quoted')
                                                        {{ $price['currency'] }}{{ number_format($price['total'] / 100, 2) }}
                                                    @else
                                                        <span class="text-gray-400">Not Quoted</span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-2 whitespace-nowrap text-right text-sm font-semibold text-gray-900 dark:text-gray-100">
                                                    @if($price['status'] == 'Quoted')
                                                        {{ $price['order_currency'] }}{{ number_format($price['converted_price'] / 100, 2) }}
                                                    @else
                                                        <span class="text-gray-400">-</span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-2 whitespace-nowrap text-center text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $price['status'] }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            @if($productComparison['savings'] > 0)
                                <div class="mt-2 text-sm text-green-600 dark:text-green-400">
                                    Savings for this product: {{ $order->currency->symbol }}{{ number_format($productComparison['savings'] / 100, 2) }}
                                    ({{ number_format($productComparison['savings_percent'], 2) }}%)
                                </div>
                            @endif
                        </div>
                    @endforeach
                </x-filament::section>
            </div>
        @else
            <x-filament::section>
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No quotes available</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">This order doesn't have any supplier quotes yet.</p>
                </div>
            </x-filament::section>
        @endif
    @else
        <x-filament::section>
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No order selected</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Select an order from the Orders page to view quote comparisons.</p>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
