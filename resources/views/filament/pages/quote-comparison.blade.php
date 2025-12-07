<x-filament-panels::page>
    <style>
        .quote-compare-container {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
        }
        .quote-selector {
            background: white;
            padding: 16px;
            border-radius: 10px;
            box-shadow: 0 1px 4px rgba(2,6,23,0.06);
            margin-bottom: 20px;
        }
        .quote-chips {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }
        .quote-chip {
            padding: 8px 16px;
            border-radius: 8px;
            border: 2px solid #e6eef8;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .quote-chip.selected {
            border-color: #0f6fff;
            background: #eff6ff;
        }
        .quote-chip:hover {
            border-color: #0f6fff;
        }
        .compare-area {
            overflow-x: auto;
            background: white;
            border-radius: 10px;
            padding: 16px;
            box-shadow: 0 1px 6px rgba(2,6,23,0.06);
            margin-bottom: 20px;
        }
        .compare-grid {
            display: grid;
            grid-auto-flow: column;
            grid-auto-columns: 280px;
            gap: 16px;
            min-width: min-content;
        }
        .quote-card {
            min-width: 280px;
            border-radius: 10px;
            padding: 16px;
            background: linear-gradient(180deg, #fff, #fbfdff);
            border: 1px solid #eef6ff;
        }
        .quote-card-header {
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f1f5f9;
        }
        .supplier-name {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .total-price {
            font-size: 28px;
            font-weight: 800;
            color: #0f6fff;
            margin: 8px 0;
        }
        .best-badge {
            display: inline-block;
            background: rgba(34,197,94,0.12);
            color: #16a34a;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-sent { background: #fef3c7; color: #92400e; }
        .status-accepted { background: #d1fae5; color: #065f46; }
        .status-draft { background: #e5e7eb; color: #374151; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        
        .spec-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-top: 1px dashed #f1f5f9;
        }
        .spec-row:first-child {
            border-top: 0;
        }
        .spec-key {
            color: #6b7280;
            font-size: 13px;
        }
        .spec-val {
            font-weight: 600;
            color: #0f172a;
            text-align: right;
        }
        .spec-val.highlight {
            background: rgba(34,197,94,0.12);
            border-radius: 6px;
            padding: 4px 8px;
            color: #16a34a;
        }
        .spec-val.warning {
            background: rgba(239,68,68,0.08);
            border-radius: 6px;
            padding: 4px 8px;
            color: #dc2626;
        }
        .section-header {
            font-size: 14px;
            font-weight: 700;
            color: #0f6fff;
            margin: 16px 0 8px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Products Grid */
        .products-section {
            background: white;
            border-radius: 10px;
            padding: 16px;
            box-shadow: 0 1px 6px rgba(2,6,23,0.06);
        }
        .products-grid {
            display: grid;
            gap: 1px;
            background: #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }
        .products-header {
            display: contents;
        }
        .product-cell {
            background: white;
            padding: 12px;
            font-size: 13px;
        }
        .product-cell.header {
            background: #f9fafb;
            font-weight: 700;
            color: #374151;
        }
        .product-cell.product-name {
            font-weight: 600;
            color: #0f172a;
        }
        .product-cell.price {
            text-align: center;
            font-weight: 600;
        }
        .product-cell.price.best {
            background: rgba(34,197,94,0.08);
            color: #16a34a;
            font-weight: 700;
        }
        .product-cell.price.not-quoted {
            color: #9ca3af;
            font-style: italic;
        }
        
        .empty-state {
            padding: 60px 20px;
            text-align: center;
            color: #6b7280;
        }
        @media (max-width: 800px) {
            .compare-grid {
                grid-auto-columns: 240px;
            }
            .quote-card {
                min-width: 240px;
            }
        }
    </style>

    @if(!$order)
        <div class="empty-state">
            <div style="font-size: 48px; margin-bottom: 16px;">📊</div>
            <h3 style="margin: 0 0 8px 0; color: #0f172a;">No Order Selected</h3>
            <p>Please select an order to compare quotes.</p>
        </div>
    @elseif(empty($comparison['overall']['all_quotes']))
        <div class="empty-state">
            <div style="font-size: 48px; margin-bottom: 16px;">📭</div>
            <h3 style="margin: 0 0 8px 0; color: #0f172a;">No Quotes Available</h3>
            <p>No supplier quotes found for this order.</p>
        </div>
    @else
        <div class="quote-compare-container">
            {{-- Quote Selector --}}
            <div class="quote-selector">
                <div style="font-size: 14px; font-weight: 600; color: #6b7280; margin-bottom: 12px;">
                    📋 Select Quotes to Compare ({{ count($selectedQuotes) }}/{{ count($comparison['overall']['all_quotes']) }} selected)
                </div>
                
                <div class="quote-chips">
                    @foreach($comparison['overall']['all_quotes'] as $quote)
                        <button
                            wire:click="toggleQuote({{ $quote['quote_id'] }})"
                            class="quote-chip {{ in_array($quote['quote_id'], $selectedQuotes) ? 'selected' : '' }}"
                        >
                            <span style="font-size: 18px;">
                                {{ in_array($quote['quote_id'], $selectedQuotes) ? '☑' : '☐' }}
                            </span>
                            <div>
                                <div style="font-weight: 600; font-size: 14px;">{{ $quote['supplier'] }}</div>
                                <div style="font-size: 12px; color: #6b7280;">
                                    {{ $order->currency->symbol }}{{ number_format($quote['total_after_commission'] / 100, 2) }}
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>

                <div style="font-size: 12px; color: #6b7280;">
                    Click on quotes to select/deselect • Min: 1, Max: 4
                </div>
            </div>

            {{-- Comparison Grid --}}
            @php
                $selectedQuotesData = collect($comparison['overall']['all_quotes'])
                    ->filter(fn($q) => in_array($q['quote_id'], $selectedQuotes))
                    ->values();
                
                $cheapestSelected = $selectedQuotesData->sortBy('total_after_commission')->first();
                
                // Find best values for highlighting
                $bestMOQ = $selectedQuotesData->filter(fn($q) => isset($q['moq']) && $q['moq'])->min('moq');
                $bestLeadTime = $selectedQuotesData->filter(fn($q) => isset($q['lead_time_days']) && $q['lead_time_days'])->min('lead_time_days');
            @endphp

            @if($selectedQuotesData->isNotEmpty())
                {{-- Supplier Cards (without products) --}}
                <div class="compare-area">
                    <div class="compare-grid">
                        @foreach($selectedQuotesData as $quote)
                            @php
                                $isBestPrice = $quote['quote_id'] === $cheapestSelected['quote_id'];
                                $priceDiff = $quote['total_after_commission'] - $cheapestSelected['total_after_commission'];
                            @endphp
                            
                            <div class="quote-card">
                                {{-- Header --}}
                                <div class="quote-card-header">
                                    <div class="supplier-name">{{ $quote['supplier'] }}</div>
                                    
                                    @php
                                        $displayTotal = isset($quote['commission_type']) && $quote['commission_type'] === 'separate' 
                                            ? $quote['total_before_commission'] 
                                            : $quote['total_after_commission'];
                                    @endphp
                                    
                                    <div class="total-price">
                                        {{ $order->currency->symbol }}{{ number_format($displayTotal / 100, 2) }}
                                    </div>
                                    
                                    @if(isset($quote['commission_type']) && $quote['commission_type'] === 'separate' && isset($quote['commission_amount']) && $quote['commission_amount'] > 0)
                                        <div style="font-size: 13px; color: #6b7280; margin-top: 4px;">
                                            + Commission: {{ $order->currency->symbol }}{{ number_format($quote['commission_amount'] / 100, 2) }}
                                        </div>
                                        <div style="font-size: 12px; color: #9ca3af; margin-top: 2px; border-top: 1px solid #e5e7eb; padding-top: 4px;">
                                            Total: {{ $order->currency->symbol }}{{ number_format($quote['total_after_commission'] / 100, 2) }}
                                        </div>
                                    @endif
                                    
                                    @if($isBestPrice)
                                        <span class="best-badge" style="margin-top: 8px; display: inline-block;">⭐ Best Price</span>
                                    @elseif($priceDiff > 0)
                                        <div style="font-size: 13px; color: #dc2626; margin-top: 4px;">
                                            +{{ $order->currency->symbol }}{{ number_format($priceDiff / 100, 2) }}
                                        </div>
                                    @endif
                                    
                                    <div style="margin-top: 8px;">
                                        <span class="status-badge status-{{ $quote['status'] }}">
                                            {{ ucfirst($quote['status']) }}
                                        </span>
                                    </div>
                                </div>

                                {{-- Procurement Details --}}
                                <div class="section-header">📦 Procurement</div>
                                
                                <div class="spec-row">
                                    <div class="spec-key">MOQ</div>
                                    <div class="spec-val {{ isset($quote['moq']) && $quote['moq'] && $quote['moq'] == $bestMOQ ? 'highlight' : '' }} {{ isset($quote['moq']) && $quote['moq'] > 500 ? 'warning' : '' }}">
                                        {{ isset($quote['moq']) && $quote['moq'] ? number_format($quote['moq']) : 'N/A' }}
                                    </div>
                                </div>

                                <div class="spec-row">
                                    <div class="spec-key">Lead Time</div>
                                    <div class="spec-val {{ isset($quote['lead_time_days']) && $quote['lead_time_days'] && $quote['lead_time_days'] == $bestLeadTime ? 'highlight' : '' }} {{ isset($quote['lead_time_days']) && $quote['lead_time_days'] > 60 ? 'warning' : '' }}">
                                        {{ isset($quote['lead_time_days']) && $quote['lead_time_days'] ? $quote['lead_time_days'] . ' days' : 'N/A' }}
                                    </div>
                                </div>

                                <div class="spec-row">
                                    <div class="spec-key">Incoterm</div>
                                    <div class="spec-val {{ isset($quote['incoterm']) && in_array($quote['incoterm'], ['DDP', 'DAP']) ? 'highlight' : '' }}">
                                        {{ $quote['incoterm'] ?? 'N/A' }}
                                    </div>
                                </div>

                                <div class="spec-row">
                                    <div class="spec-key">Payment</div>
                                    <div class="spec-val {{ isset($quote['payment_terms']) && !str_contains($quote['payment_terms'], '100') ? 'highlight' : '' }}">
                                        @if(isset($quote['payment_terms']))
                                            {{ str_replace('_', ' ', ucwords($quote['payment_terms'], '_')) }}
                                        @else
                                            N/A
                                        @endif
                                    </div>
                                </div>

                                {{-- Additional Info --}}
                                <div class="section-header">ℹ️ Details</div>
                                
                                <div class="spec-row">
                                    <div class="spec-key">Quote ID</div>
                                    <div class="spec-val" style="font-size: 12px;">#{{ $quote['quote_id'] }}</div>
                                </div>

                                <div class="spec-row">
                                    <div class="spec-key">Currency</div>
                                    <div class="spec-val">{{ $quote['currency'] }}</div>
                                </div>

                                @if(isset($quote['exchange_rate']) && $quote['exchange_rate'] != 1)
                                    <div class="spec-row">
                                        <div class="spec-key">Exchange Rate</div>
                                        <div class="spec-val" style="font-size: 12px;">{{ number_format($quote['exchange_rate'], 4) }}</div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Products Grid (Separate, Aligned) --}}
                @if(isset($comparison['by_product']) && count($comparison['by_product']) > 0)
                    <div class="products-section">
                        <div style="font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 16px;">
                            📦 Products Comparison (Unit Prices)
                        </div>
                        
                        @php
                            $colCount = $selectedQuotesData->count() + 1; // +1 for product name column
                        @endphp
                        
                        <div class="products-grid" style="grid-template-columns: 200px repeat({{ $selectedQuotesData->count() }}, 1fr);">
                            {{-- Header Row --}}
                            <div class="product-cell header">Product</div>
                            @foreach($selectedQuotesData as $quote)
                                <div class="product-cell header" style="text-align: center;">{{ $quote['supplier'] }}</div>
                            @endforeach
                            
                            {{-- Product Rows --}}
                            @foreach($comparison['by_product'] as $productComparison)
                                {{-- Product Name --}}
                                <div class="product-cell product-name" title="{{ $productComparison['product'] ?? 'Unknown' }}">
                                    {{ $productComparison['product'] ?? 'Unknown' }}
                                    @if(isset($productComparison['product_code']))
                                        <div style="font-size: 11px; color: #6b7280; font-weight: 400;">{{ $productComparison['product_code'] }}</div>
                                    @endif
                                </div>
                                
                                {{-- Prices for each supplier --}}
                                @foreach($selectedQuotesData as $quote)
                                    @php
                                        $productPrice = collect($productComparison['all_prices'])
                                            ->firstWhere('supplier_id', $quote['supplier_id']);
                                        
                                        $isCheapestProduct = isset($productComparison['cheapest']) && 
                                            $productPrice && 
                                            $productPrice['supplier_id'] === $productComparison['cheapest']['supplier_id'];
                                    @endphp
                                    
                                    <div class="product-cell price {{ $isCheapestProduct ? 'best' : '' }} {{ !$productPrice || !$productPrice['price'] ? 'not-quoted' : '' }}">
                                        @if($productPrice && $productPrice['price'])
                                            @php
                                                // Show price WITHOUT commission if type is 'separate'
                                                $displayPrice = isset($productPrice['commission_type']) && $productPrice['commission_type'] === 'separate'
                                                    ? $productPrice['price_before_commission']
                                                    : $productPrice['price'];
                                            @endphp
                                            
                                            <div style="font-weight: 700;">
                                                {{ $order->currency->symbol }}{{ number_format($displayPrice / 100, 2) }}
                                                @if($isCheapestProduct)
                                                    <span style="margin-left: 4px;">⭐</span>
                                                @endif
                                            </div>
                                        @else
                                            Not quoted
                                        @endif
                                    </div>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                @endif
            @else
                <div class="empty-state">
                    <div style="font-size: 48px; margin-bottom: 16px;">👆</div>
                    <h3 style="margin: 0 0 8px 0; color: #0f172a;">Select Quotes Above</h3>
                    <p>Click on quote chips above to start comparing.</p>
                </div>
            @endif
        </div>
    @endif
</x-filament-panels::page>
