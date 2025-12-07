<x-filament-panels::page>
    @if(!$order)
        <x-filament::section>
            <x-slot name="heading">
                No Order Selected
            </x-slot>

            <div class="text-center py-12">
                <p class="text-gray-500 dark:text-gray-400">
                    Please select an order to compare quotes.
                </p>
            </div>
        </x-filament::section>
    @elseif(empty($comparison['overall']['all_quotes']))
        <x-filament::section>
            <x-slot name="heading">
                No Quotes Available
            </x-slot>

            <div class="text-center py-12">
                <p class="text-gray-500 dark:text-gray-400">
                    No supplier quotes found for this order.
                </p>
            </div>
        </x-filament::section>
    @else
        {{-- Quote Selector --}}
        <x-filament::section>
            <x-slot name="heading">
                📋 Select Quotes to Compare
            </x-slot>
            
            <x-slot name="description">
                Choose 2-4 quotes to compare side-by-side. Click on cards to select/deselect.
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($comparison['overall']['all_quotes'] as $quote)
                    <button
                        wire:click="toggleQuote({{ $quote['quote_id'] }})"
                        class="relative p-6 rounded-lg border-2 transition-all duration-200 text-left
                            {{ in_array($quote['quote_id'], $selectedQuotes) 
                                ? 'border-primary-500 bg-primary-50 dark:bg-primary-500/10 shadow-lg' 
                                : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600' }}"
                    >
                        {{-- Checkbox --}}
                        <div class="absolute top-4 right-4">
                            @if(in_array($quote['quote_id'], $selectedQuotes))
                                <div class="w-6 h-6 rounded bg-primary-500 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            @else
                                <div class="w-6 h-6 rounded border-2 border-gray-300 dark:border-gray-600"></div>
                            @endif
                        </div>

                        {{-- Supplier Name --}}
                        <div class="mb-3">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white pr-8">
                                {{ $quote['supplier'] }}
                            </h3>
                        </div>

                        {{-- Total Price --}}
                        <div class="mb-2">
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $order->currency->symbol }}{{ number_format($quote['total_after_commission'] / 100, 2) }}
                            </div>
                        </div>

                        {{-- Best Price Badge --}}
                        @if($quote['quote_id'] === $comparison['overall']['cheapest_quote_id'])
                            <x-filament::badge color="success" size="sm">
                                ⭐ Best Price
                            </x-filament::badge>
                        @endif

                        {{-- Status --}}
                        <div class="mt-3">
                            <x-filament::badge
                                :color="match($quote['status']) {
                                    'accepted' => 'success',
                                    'sent' => 'warning',
                                    'rejected' => 'danger',
                                    default => 'gray'
                                }"
                                size="sm"
                            >
                                {{ ucfirst($quote['status']) }}
                            </x-filament::badge>
                        </div>

                        {{-- Quick Info --}}
                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-400 space-y-1">
                            @if($quote['moq'])
                                <div>MOQ: {{ number_format($quote['moq']) }}</div>
                            @endif
                            @if($quote['lead_time_days'])
                                <div>Lead: {{ $quote['lead_time_days'] }}d</div>
                            @endif
                        </div>
                    </button>
                @endforeach
            </div>

            <div class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                {{ count($selectedQuotes) }} of {{ count($comparison['overall']['all_quotes']) }} quotes selected
                (min: 1, max: 4)
            </div>
        </x-filament::section>

        {{-- Side-by-Side Comparison --}}
        @php
            $selectedQuotesData = collect($comparison['overall']['all_quotes'])
                ->filter(fn($q) => in_array($q['quote_id'], $selectedQuotes))
                ->values();
            
            $cheapestSelected = $selectedQuotesData->sortBy('total_after_commission')->first();
        @endphp

        @if($selectedQuotesData->isNotEmpty())
            <x-filament::section class="mt-6">
                <x-slot name="heading">
                    🔍 Side-by-Side Comparison
                </x-slot>

                <div class="overflow-x-auto">
                    <div class="inline-flex min-w-full">
                        {{-- Comparison Table --}}
                        <div class="flex-1 grid gap-px bg-gray-200 dark:bg-gray-700" 
                             style="grid-template-columns: 200px repeat({{ $selectedQuotesData->count() }}, 1fr);">
                            
                            {{-- Header Row --}}
                            <div class="bg-gray-50 dark:bg-gray-800 p-4 font-semibold text-gray-900 dark:text-white">
                                Criteria
                            </div>
                            @foreach($selectedQuotesData as $quote)
                                <div class="bg-gray-50 dark:bg-gray-800 p-4 text-center">
                                    <div class="font-semibold text-gray-900 dark:text-white mb-2">
                                        {{ $quote['supplier'] }}
                                    </div>
                                    @if($quote['quote_id'] === $cheapestSelected['quote_id'])
                                        <x-filament::badge color="success" size="sm">
                                            ⭐ Best Price
                                        </x-filament::badge>
                                    @endif
                                </div>
                            @endforeach

                            {{-- PRICING SECTION --}}
                            <div class="bg-primary-50 dark:bg-primary-900/20 p-3 font-bold text-primary-700 dark:text-primary-300" 
                                 style="grid-column: 1 / -1;">
                                💰 PRICING
                            </div>

                            {{-- Total Price --}}
                            <div class="bg-white dark:bg-gray-900 p-4 font-medium text-gray-700 dark:text-gray-300">
                                Total Price
                            </div>
                            @foreach($selectedQuotesData as $quote)
                                @php
                                    $isCheapest = $quote['quote_id'] === $cheapestSelected['quote_id'];
                                    $priceDiff = $quote['total_after_commission'] - $cheapestSelected['total_after_commission'];
                                @endphp
                                <div class="bg-white dark:bg-gray-900 p-4 text-center {{ $isCheapest ? 'bg-success-50 dark:bg-success-900/20' : '' }}">
                                    <div class="text-xl font-bold {{ $isCheapest ? 'text-success-700 dark:text-success-300' : 'text-gray-900 dark:text-white' }}">
                                        {{ $order->currency->symbol }}{{ number_format($quote['total_after_commission'] / 100, 2) }}
                                    </div>
                                    @if(!$isCheapest && $priceDiff > 0)
                                        <div class="text-sm text-danger-600 dark:text-danger-400 mt-1">
                                            +{{ $order->currency->symbol }}{{ number_format($priceDiff / 100, 2) }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach

                            {{-- PROCUREMENT SECTION --}}
                            <div class="bg-primary-50 dark:bg-primary-900/20 p-3 font-bold text-primary-700 dark:text-primary-300" 
                                 style="grid-column: 1 / -1;">
                                📦 PROCUREMENT DETAILS
                            </div>

                            {{-- MOQ --}}
                            <div class="bg-white dark:bg-gray-900 p-4 font-medium text-gray-700 dark:text-gray-300">
                                MOQ
                            </div>
                            @foreach($selectedQuotesData as $quote)
                                @php
                                    $moq = $quote['moq'] ?? null;
                                    $isHigh = $moq && $moq > 500;
                                @endphp
                                <div class="bg-white dark:bg-gray-900 p-4 text-center">
                                    @if($moq)
                                        <div class="text-gray-900 dark:text-white">
                                            {{ number_format($moq) }}
                                            @if($isHigh)
                                                <x-filament::badge color="warning" size="sm">⚠️</x-filament::badge>
                                            @else
                                                <x-filament::badge color="success" size="sm">✅</x-filament::badge>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-gray-400">N/A</span>
                                    @endif
                                </div>
                            @endforeach

                            {{-- Lead Time --}}
                            <div class="bg-white dark:bg-gray-900 p-4 font-medium text-gray-700 dark:text-gray-300">
                                Lead Time
                            </div>
                            @foreach($selectedQuotesData as $quote)
                                @php
                                    $leadTime = $quote['lead_time_days'] ?? null;
                                    $isLong = $leadTime && $leadTime > 60;
                                @endphp
                                <div class="bg-white dark:bg-gray-900 p-4 text-center">
                                    @if($leadTime)
                                        <div class="text-gray-900 dark:text-white">
                                            {{ $leadTime }} days
                                            @if($isLong)
                                                <x-filament::badge color="danger" size="sm">⚠️</x-filament::badge>
                                            @else
                                                <x-filament::badge color="success" size="sm">✅</x-filament::badge>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-gray-400">N/A</span>
                                    @endif
                                </div>
                            @endforeach

                            {{-- Incoterm --}}
                            <div class="bg-white dark:bg-gray-900 p-4 font-medium text-gray-700 dark:text-gray-300">
                                Incoterm
                            </div>
                            @foreach($selectedQuotesData as $quote)
                                <div class="bg-white dark:bg-gray-900 p-4 text-center">
                                    @if($quote['incoterm'])
                                        <x-filament::badge 
                                            :color="in_array($quote['incoterm'], ['DDP', 'DAP']) ? 'success' : 'info'" 
                                            size="sm"
                                        >
                                            {{ $quote['incoterm'] }}
                                        </x-filament::badge>
                                        @if(in_array($quote['incoterm'], ['DDP', 'DAP']))
                                            <div class="text-xs text-success-600 dark:text-success-400 mt-1">All-inclusive</div>
                                        @endif
                                    @else
                                        <span class="text-gray-400">N/A</span>
                                    @endif
                                </div>
                            @endforeach

                            {{-- Payment Terms --}}
                            <div class="bg-white dark:bg-gray-900 p-4 font-medium text-gray-700 dark:text-gray-300">
                                Payment Terms
                            </div>
                            @foreach($selectedQuotesData as $quote)
                                @php
                                    $payment = $quote['payment_terms'] ?? null;
                                    $isGood = $payment && !str_contains($payment, '100');
                                @endphp
                                <div class="bg-white dark:bg-gray-900 p-4 text-center text-sm">
                                    @if($payment)
                                        <div class="text-gray-900 dark:text-white">
                                            {{ str_replace('_', ' ', ucwords($payment, '_')) }}
                                        </div>
                                        @if($isGood)
                                            <x-filament::badge color="success" size="sm">✅</x-filament::badge>
                                        @else
                                            <x-filament::badge color="warning" size="sm">⚠️</x-filament::badge>
                                        @endif
                                    @else
                                        <span class="text-gray-400">N/A</span>
                                    @endif
                                </div>
                            @endforeach

                            {{-- STATUS --}}
                            <div class="bg-white dark:bg-gray-900 p-4 font-medium text-gray-700 dark:text-gray-300">
                                Status
                            </div>
                            @foreach($selectedQuotesData as $quote)
                                <div class="bg-white dark:bg-gray-900 p-4 text-center">
                                    <x-filament::badge
                                        :color="match($quote['status']) {
                                            'accepted' => 'success',
                                            'sent' => 'warning',
                                            'rejected' => 'danger',
                                            default => 'gray'
                                        }"
                                        size="sm"
                                    >
                                        {{ ucfirst($quote['status']) }}
                                    </x-filament::badge>
                                </div>
                            @endforeach

                            {{-- PRODUCTS SECTION --}}
                            @if(isset($comparison['by_product']) && count($comparison['by_product']) > 0)
                                <div class="bg-primary-50 dark:bg-primary-900/20 p-3 font-bold text-primary-700 dark:text-primary-300" 
                                     style="grid-column: 1 / -1;">
                                    📦 PRODUCTS
                                </div>

                                @foreach($comparison['by_product'] as $productComparison)
                                    {{-- Product Name --}}
                                    <div class="bg-gray-50 dark:bg-gray-800 p-4 font-semibold text-gray-900 dark:text-white" 
                                         style="grid-column: 1 / -1;">
                                        {{ $productComparison['product'] ?? 'Unknown Product' }}
                                        @if(isset($productComparison['product_code']))
                                            <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">({{ $productComparison['product_code'] }})</span>
                                        @endif
                                    </div>

                                    {{-- Unit Price Label --}}
                                    <div class="bg-white dark:bg-gray-900 p-4 font-medium text-gray-700 dark:text-gray-300 pl-8">
                                        Unit Price
                                    </div>

                                    @foreach($selectedQuotesData as $quote)
                                        @php
                                            $productPrice = collect($productComparison['all_prices'])
                                                ->firstWhere('supplier_id', $quote['supplier_id']);
                                            
                                            $isCheapestProduct = isset($productComparison['cheapest']) && 
                                                $productPrice && 
                                                $productPrice['supplier_id'] === $productComparison['cheapest']['supplier_id'];
                                        @endphp
                                        <div class="bg-white dark:bg-gray-900 p-4 text-center {{ $isCheapestProduct ? 'bg-success-50 dark:bg-success-900/20' : '' }}">
                                            @if($productPrice && $productPrice['price'])
                                                <div class="font-mono {{ $isCheapestProduct ? 'text-success-700 dark:text-success-300 font-bold' : 'text-gray-900 dark:text-white' }}">
                                                    {{ $order->currency->symbol }}{{ number_format($productPrice['converted_price'] / 100, 2) }}
                                                </div>
                                                @if($isCheapestProduct)
                                                    <x-filament::badge color="success" size="sm">⭐</x-filament::badge>
                                                @endif
                                            @else
                                                <span class="text-gray-400">Not quoted</span>
                                            @endif
                                        </div>
                                    @endforeach
                                @endforeach
                            @endif

                        </div>
                    </div>
                </div>
            </x-filament::section>
        @endif
    @endif
</x-filament-panels::page>
