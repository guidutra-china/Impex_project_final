<x-filament-panels::page>
    <style>
        .quote-compare-container {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
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
        
        @media (max-width: 800px) {
            .compare-grid {
                grid-auto-columns: 240px;
            }
            .quote-card {
                min-width: 240px;
            }
        }
    </style>

    <div class="quote-compare-container">
        {{-- Quote Information Header --}}
        <div style="background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 1px 4px rgba(2,6,23,0.06);">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                <div>
                    <div style="font-size: 12px; color: #6b7280; margin-bottom: 4px;">Quote Number</div>
                    <div style="font-size: 18px; font-weight: 700; color: #0f172a;">{{ $record->quote_number }}</div>
                </div>
                <div>
                    <div style="font-size: 12px; color: #6b7280; margin-bottom: 4px;">RFQ</div>
                    <div style="font-size: 18px; font-weight: 700; color: #0f172a;">{{ $record->order->order_number ?? 'N/A' }}</div>
                </div>
                <div>
                    <div style="font-size: 12px; color: #6b7280; margin-bottom: 4px;">Status</div>
                    <div>
                        <span style="display: inline-block; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; 
                            background: {{ $record->status === 'sent' ? '#fef3c7' : ($record->status === 'accepted' ? '#d1fae5' : '#e5e7eb') }};
                            color: {{ $record->status === 'sent' ? '#92400e' : ($record->status === 'accepted' ? '#065f46' : '#374151') }};">
                            {{ ucfirst($record->status) }}
                        </span>
                    </div>
                </div>
                <div>
                    <div style="font-size: 12px; color: #6b7280; margin-bottom: 4px;">Expires</div>
                    <div style="font-size: 14px; font-weight: 600; color: {{ $record->expires_at < now() ? '#dc2626' : '#16a34a' }};">
                        {{ $record->expires_at->format('M d, Y') }}
                    </div>
                </div>
            </div>
        </div>

        @php
            $items = $record->items->load('supplierQuote.supplier', 'supplierQuote.items.product');
            $cheapestItem = $items->sortBy('price_after_commission')->first();
        @endphp

        {{-- Supplier Cards Comparison --}}
        <div class="compare-area">
            <div class="compare-grid">
                @foreach($items as $item)
                    @php
                        $isBest = $item->id === $cheapestItem->id;
                        $supplierQuote = $item->supplierQuote;
                        $supplier = $supplierQuote->supplier ?? null;
                    @endphp
                    
                    <div class="quote-card">
                        {{-- Header --}}
                        <div class="quote-card-header">
                            <div class="supplier-name">{{ $item->display_name }}</div>
                            @if($supplier)
                                <div style="font-size: 13px; color: #6b7280;">{{ $supplier->name }}</div>
                            @endif
                            
                            <div class="total-price">
                                ${{ number_format($item->price_after_commission / 100, 2) }}
                            </div>
                            
                            @if($isBest)
                                <span class="best-badge">💰 Best Price</span>
                            @endif
                        </div>

                        {{-- Specifications --}}
                        <div>
                            @if($item->delivery_time)
                                <div class="spec-row">
                                    <div class="spec-key">Delivery Time</div>
                                    <div class="spec-val">{{ $item->delivery_time }}</div>
                                </div>
                            @endif

                            @if($item->moq)
                                <div class="spec-row">
                                    <div class="spec-key">MOQ</div>
                                    <div class="spec-val">{{ number_format($item->moq) }}</div>
                                </div>
                            @endif

                            @if($item->highlights)
                                <div class="spec-row">
                                    <div class="spec-key">Highlights</div>
                                    <div class="spec-val" style="font-size: 12px; max-width: 150px;">{{ $item->highlights }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Products Grid --}}
        @php
            // Group products from all supplier quotes
            $allProducts = collect();
            foreach($items as $item) {
                if($item->supplierQuote && $item->supplierQuote->items) {
                    foreach($item->supplierQuote->items as $sqItem) {
                        $productId = $sqItem->product_id;
                        if(!$allProducts->has($productId)) {
                            $allProducts->put($productId, [
                                'product' => $sqItem->product,
                                'prices' => collect()
                            ]);
                        }
                        $allProducts[$productId]['prices']->push([
                            'supplier_quote_id' => $item->supplier_quote_id,
                            'display_name' => $item->display_name,
                            'price' => $sqItem->unit_price,
                            'quantity' => $sqItem->quantity,
                        ]);
                    }
                }
            }
        @endphp

        @if($allProducts->isNotEmpty())
            <div class="products-section">
                <div style="font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 16px;">
                    📦 Products Comparison (Unit Prices)
                </div>
                
                <div class="products-grid" style="grid-template-columns: 250px repeat({{ $items->count() }}, 1fr);">
                    {{-- Header Row --}}
                    <div class="product-cell header">Product</div>
                    @foreach($items as $item)
                        <div class="product-cell header" style="text-align: center;">{{ $item->display_name }}</div>
                    @endforeach
                    
                    {{-- Product Rows --}}
                    @foreach($allProducts as $productData)
                        @php
                            $product = $productData['product'];
                            $prices = $productData['prices'];
                            $cheapestPrice = $prices->min('price');
                        @endphp
                        
                        {{-- Product Name --}}
                        <div class="product-cell product-name">
                            {{ $product->name ?? 'Unknown Product' }}
                            @if($product->code)
                                <div style="font-size: 11px; color: #6b7280; font-weight: 400;">{{ $product->code }}</div>
                            @endif
                        </div>
                        
                        {{-- Prices for each supplier --}}
                        @foreach($items as $item)
                            @php
                                $priceData = $prices->firstWhere('supplier_quote_id', $item->supplier_quote_id);
                                $isCheapest = $priceData && $priceData['price'] === $cheapestPrice;
                            @endphp
                            
                            <div class="product-cell price {{ $isCheapest ? 'best' : '' }} {{ !$priceData ? 'not-quoted' : '' }}">
                                @if($priceData)
                                    <div style="font-weight: 700;">
                                        ${{ number_format($priceData['price'] / 100, 2) }}
                                        @if($isCheapest)
                                            <span style="margin-left: 4px;">⭐</span>
                                        @endif
                                    </div>
                                    <div style="font-size: 11px; color: #6b7280; margin-top: 2px;">
                                        Qty: {{ number_format($priceData['quantity']) }}
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
    </div>
</x-filament-panels::page>
