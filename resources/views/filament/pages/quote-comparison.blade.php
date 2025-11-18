<<x-filament-panels::page>
    @if($order)
        {{-- 1. Header Section: Order Information + Quote Statistics --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            {{-- Order Information Card --}}
            <x-filament::section class="lg:col-span-2">
                <x-slot name="heading">
                    Order Information
                </x-slot>

                <div class="text-sm space-y-1">
                    <span class="font-medium text-gray-900 dark:text-white">Order Number:</span>
                    <span class="ml-2 text-gray-700 dark:text-gray-300">{{ $order->order_number }}</span>
                    <span class="mx-4">|</span>
                    <span class="font-medium text-gray-900 dark:text-white">Customer:</span>
                    <span class="ml-2 text-gray-700 dark:text-gray-300">{{ $order->customer->name }}</span>
                    <span class="mx-4">|</span>
                    <span class="font-medium text-gray-900 dark:text-white">Currency:</span>
                    <span class="ml-2 text-gray-700 dark:text-gray-300">{{ $order->currency->symbol }} {{ $order->currency->code }}</span>
                    <span class="mx-4">|</span>
                    <span class="font-medium text-gray-900 dark:text-white">Commission:</span>
                    <span class="ml-2 text-gray-700 dark:text-gray-300">{{ $order->commission_percent }}% ({{ ucfirst($order->commission_type) }})</span>
                </div>
            </x-filament::section>

            {{-- Quote Statistics Card --}}
            @if($summary)
                <x-filament::section class="flex flex-col justify-center">
                    <x-slot name="heading">
                        Quote Statistics
                    </x-slot>

                    <div class="flex flex-wrap gap-4 text-sm">
                        <x-filament::badge color="primary" class="px-4 py-2 text-base">
                            Total: {{ $summary['total_quotes'] ?? 0 }}
                        </x-filament::badge>
                        <x-filament::badge color="gray" class="px-4 py-2 text-base">
                            Draft: {{ $summary['draft_quotes'] ?? 0 }}
                        </x-filament::badge>
                        <x-filament::badge color="warning" class="px-4 py-2 text-base">
                            Sent: {{ $summary['sent_quotes'] ?? 0 }}
                        </x-filament::badge>
                        <x-filament::badge color="success" class="px-4 py-2 text-base">
                            Accepted: {{ $summary['accepted_quotes'] ?? 0 }}
                        </x-filament::badge>
                    </div>
                </x-filament::section>
            @endif
        </div>

        @if(!empty($comparison['overall']))
            {{-- 2. Savings Highlight --}}
            @if($comparison['overall']['savings'] > 0)
                <div class="mb-8">
                    <div class="rounded-lg bg-gradient-to-r from-success-50 to-success-100 dark:from-success-500/10 dark:to-success-500/5 p-8 border border-success-200 dark:border-success-500/20">
                        <div class="flex items-center gap-6">
                            <div class="flex-shrink-0 text-5xl">
                                💰
                            </div>
                            <div class="flex-1">
                                <p class="text-xl font-semibold text-success-900 dark:text-success-200">
                                    Best Supplier: <span class="text-success-700 dark:text-success-300">{{ $comparison['overall']['cheapest_supplier'] }}</span>
                                </p>
                                <p class="text-lg text-success-800 dark:text-success-300 mt-2">
                                    Potential Savings:
                                    <span class="font-bold text-2xl mx-2">{{ $order->currency->symbol }}{{ number_format($comparison['overall']['savings'] / 100, 2) }}</span>
                                    <span class="font-semibold text-xl">({{ number_format($comparison['overall']['savings_percent'], 2) }}%)</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- 3. Overall Supplier Comparison Table --}}
            <x-filament::section class="mb-8">
                <x-slot name="heading">
                    Overall Supplier Comparison
                </x-slot>
                <x-slot name="description">
                    All prices converted to {{ $order->currency->code }} for fair comparison
                </x-slot>

                <div class="overflow-x-auto">
                    <table class="w-full table-fixed divide-y divide-gray-200 dark:divide-white/10 text-base">
                        <thead class="bg-gray-50 dark:bg-white/5 sticky top-0 z-10">
                        <tr>
                            <th scope="col" class="w-[35%] px-8 py-6 text-left font-semibold text-gray-900 dark:text-white text-lg">Supplier</th>
                            <th scope="col" class="w-[25%] px-8 py-6 text-right font-semibold text-gray-900 dark:text-white text-lg">Total Price ({{ $order->currency->code }})</th>
                            <th scope="col" class="w-[15%] px-8 py-6 text-center font-semibold text-gray-900 dark:text-white text-lg">Status</th>
                            <th scope="col" class="w-[25%] px-8 py-6 text-right font-semibold text-gray-900 dark:text-white text-lg">vs. Best Price</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5 bg-white dark:bg-transparent">
                        @php
                            $cheapestTotal = collect($comparison['overall']['all_quotes'])->firstWhere('quote_id', $comparison['overall']['cheapest_quote_id'])['total_after_commission'] ?? 0;
                        @endphp
                        @foreach($comparison['overall']['all_quotes'] as $quote)
                            @php
                                $isCheapest = $quote['quote_id'] === $comparison['overall']['cheapest_quote_id'];
                                $priceDiff = $quote['total_after_commission'] - $cheapestTotal;
                            @endphp
                            <tr class="{{ $isCheapest ? 'bg-success-50 dark:bg-success-500/10 font-semibold' : 'hover:bg-gray-50 dark:hover:bg-white/5' }}">
                                <td class="px-8 py-6 whitespace-nowrap">
                                    <div class="flex items-center gap-4">
                                        <span class="text-gray-900 dark:text-white text-lg">{{ $quote['supplier'] }}</span>
                                        @if($isCheapest)
                                            <x-filament::badge color="success" class="px-4 py-2 text-base">
                                                ⭐ Best Price
                                            </x-filament::badge>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-right whitespace-nowrap">
                                        <span class="font-mono text-lg {{ $isCheapest ? 'text-success-700 dark:text-success-300 font-bold' : 'text-gray-900 dark:text-white' }}">
                                            {{ $order->currency->symbol }}{{ number_format($quote['total_after_commission'] / 100, 2) }}
                                        </span>
                                </td>
                                <td class="px-8 py-6 text-center whitespace-nowrap">
                                    <x-filament::badge
                                            :color="match($quote['status']) {
                                                'accepted' => 'success',
                                                'sent' => 'warning',
                                                'rejected' => 'danger',
                                                default => 'gray'
                                            }"
                                            class="px-4 py-2 text-base"
                                    >
                                        {{ ucfirst($quote['status']) }}
                                    </x-filament::badge>
                                </td>
                                <td class="px-8 py-6 text-right whitespace-nowrap">
                                    @if($isCheapest)
                                        <span class="text-gray-500 dark:text-gray-400 text-lg">-</span>
                                    @else
                                        <span class="text-danger-600 dark:text-danger-400 font-semibold text-lg">
                                                +{{ $order->currency->symbol }}{{ number_format($priceDiff / 100, 2) }}
                                            </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>

            {{-- 4. Product-by-Product Detailed Comparison --}}
            @if(count($comparison['by_product']) > 0)
                <x-filament::section>
                    <x-slot name="heading">
                        Product-by-Product Comparison
                    </x-slot>
                    <x-slot name="description">
                        Detailed price breakdown for each product
                    </x-slot>

                    <div class="space-y-8">
                        @foreach($comparison['by_product'] as $productComparison)
                            <div class="rounded-lg border border-gray-200 dark:border-white/10 overflow-hidden bg-white dark:bg-transparent">
                                {{-- Product Header --}}
                                <div class="bg-gray-50 dark:bg-white/5 px-8 py-6 border-b border-gray-200 dark:border-white/10">
                                    <div class="flex flex-wrap items-center justify-between gap-4">
                                        <div class="flex items-baseline gap-4">
                                            <p class="text-xl font-bold text-gray-900 dark:text-white">
                                                {{ $productComparison['product'] }}
                                                <span class="text-base font-normal text-gray-600 dark:text-gray-400 ml-2">({{ $productComparison['product_code'] }})</span>
                                            </p>
                                            <span class="text-gray-400 dark:text-gray-500">|</span>
                                            <p class="text-base text-gray-600 dark:text-gray-400">
                                                Quantity: <span class="font-semibold text-gray-900 dark:text-white">{{ $productComparison['quantity'] }}</span>
                                            </p>
                                        </div>
                                        @if($productComparison['savings'] > 0)
                                            <div class="bg-success-50 dark:bg-success-500/10 px-6 py-3 rounded-lg">
                                                <p class="text-base font-bold text-success-700 dark:text-success-300">
                                                    💰 Savings: {{ $order->currency->symbol }}{{ number_format($productComparison['savings'] / 100, 2) }}
                                                </p>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Product Prices Table --}}
                                <div class="overflow-x-auto">
                                    <table class="w-full divide-y divide-gray-200 dark:divide-white/10 text-base">
                                        <thead class="bg-gray-100 dark:bg-white/5">
                                        <tr>
                                            <th scope="col" class="px-6 py-4 text-left font-semibold text-gray-900 dark:text-white w-[22%]">Supplier</th>
                                            <th scope="col" class="px-4 py-4 text-right font-semibold text-gray-900 dark:text-white w-[16%]">Unit Price</th>
                                            <th scope="col" class="px-4 py-4 text-right font-semibold text-gray-900 dark:text-white w-[18%]">Total (Original)</th>
                                            <th scope="col" class="px-4 py-4 text-right font-semibold text-gray-900 dark:text-white w-[18%]">Price ({{ $order->currency->code }})</th>
                                            <th scope="col" class="px-4 py-4 text-center font-semibold text-gray-900 dark:text-white w-[13%]">Status</th>
                                            <th scope="col" class="px-4 py-4 text-center font-semibold text-gray-900 dark:text-white w-[13%]">Best</th>
                                        </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                        @foreach($productComparison['all_prices'] as $price)
                                            @php
                                                $isCheapest = $price['supplier_id'] === $productComparison['cheapest']['supplier_id'];
                                            @endphp
                                            <tr class="{{ $isCheapest ? 'bg-success-50 dark:bg-success-500/10 font-semibold' : 'hover:bg-gray-50 dark:hover:bg-white/5' }}">
                                                <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white text-base">
                                                    {{ $price['supplier'] }}
                                                </td>
                                                <td class="px-4 py-4 text-right whitespace-nowrap text-gray-700 dark:text-gray-300">
                                                    <span class="font-mono text-base">
                                                        @if($price['price'])
                                                            {{ $price['currency'] ?? '' }} {{ number_format($price['price'] / 100, 2) }}
                                                        @else
                                                            <span class="text-gray-400">N/A</span>
                                                        @endif
                                                    </span>
                                                </td>
                                                <td class="px-4 py-4 text-right whitespace-nowrap text-gray-700 dark:text-gray-300">
                                                    <span class="font-mono text-base">
                                                        @if(isset($price['total']) && $price['total'])
                                                            {{ $price['currency'] ?? '' }} {{ number_format($price['total'] / 100, 2) }}
                                                        @else
                                                            <span class="text-gray-400">N/A</span>
                                                        @endif
                                                    </span>
                                                </td>
                                                <td class="px-4 py-4 text-right whitespace-nowrap">
                                                    <span class="font-mono text-lg {{ $isCheapest ? 'text-success-700 dark:text-success-300 font-bold' : 'text-gray-900 dark:text-white' }}">
                                                        @if($price['converted_price'])
                                                            {{ $order->currency->symbol }}{{ number_format($price['converted_price'] / 100, 2) }}
                                                        @else
                                                            <span class="text-gray-400">N/A</span>
                                                        @endif
                                                    </span>
                                                </td>
                                                <td class="px-4 py-4 text-center whitespace-nowrap">
                                                    <x-filament::badge
                                                            :color="match($price['status']) {
                                                            'accepted' => 'success',
                                                            'sent' => 'warning',
                                                            'rejected' => 'danger',
                                                            default => 'gray'
                                                        }"
                                                            class="px-4 py-2 text-base"
                                                    >
                                                        {{ ucfirst($price['status']) }}
                                                    </x-filament::badge>
                                                </td>
                                                <td class="px-4 py-4 text-center whitespace-nowrap">
                                                    @if($isCheapest)
                                                        <x-filament::badge color="success" class="px-4 py-2 text-base">⭐ Best</x-filament::badge>
                                                    @else
                                                        <span class="text-gray-400 dark:text-gray-600 text-lg">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            @endif
        @else
            {{-- No Comparison Data --}}
            <x-filament::section>
                <div class="text-center py-16">
                    <div class="text-6xl mb-6">📊</div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">No Quotes Available</h3>
                    <p class="text-base text-gray-600 dark:text-gray-400">
                        Add supplier quotes to this order to see the comparison analysis.
                    </p>
                </div>
            </x-filament::section>
        @endif
    @else
        {{-- No Order Selected --}}
        <x-filament::section>
            <div class="text-center py-16">
                <div class="text-6xl mb-6">🔍</div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">No Order Selected</h3>
                <p class="text-base text-gray-600 dark:text-gray-400">
                    Please select an order from the dropdown above to view quote comparisons.
                </p>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>