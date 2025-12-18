{{-- Comparison Mode with Product Selection --}}

@php
    // Get visible items (supplier options)
    $visibleItems = $customerQuote->items->where('is_visible_to_customer', true);
    $cheapestItem = $visibleItems->sortBy('price_after_commission')->first();
    
    // Group products from all supplier quotes
    $allProducts = collect();
    foreach($visibleItems as $customerQuoteItem) {
        $supplierQuote = $customerQuoteItem->supplierQuote;
        if($supplierQuote && $supplierQuote->items) {
            foreach($supplierQuote->items as $quoteItem) {
                $productId = $quoteItem->product_id;
                if(!$allProducts->has($productId)) {
                    $allProducts->put($productId, [
                        'product' => $quoteItem->product,
                        'items' => collect()
                    ]);
                }
                $allProducts[$productId]['items']->push([
                    'quote_item_id' => $quoteItem->id,
                    'supplier_quote_id' => $customerQuoteItem->supplier_quote_id,
                    'supplier_name' => $customerQuoteItem->display_name,
                    'price' => $quoteItem->unit_price_after_commission,
                    'quantity' => $quoteItem->quantity,
                    'lead_time_days' => $quoteItem->lead_time_days,
                ]);
            }
        }
    }
@endphp

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
    .products-section {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 1px 6px rgba(2,6,23,0.06);
    }
    .products-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    .products-table th {
        background: #f8fafc;
        padding: 12px 16px;
        text-align: left;
        font-weight: 700;
        color: #0f172a;
        border-bottom: 2px solid #e2e8f0;
    }
    .products-table td {
        padding: 16px;
        border-bottom: 1px solid #f1f5f9;
    }
    .products-table tr:hover {
        background: #f8fafc;
    }
    .product-name-cell {
        font-weight: 600;
        color: #0f172a;
    }
    .supplier-option-cell {
        text-align: center;
        position: relative;
    }
    .supplier-option-cell.selectable {
        cursor: pointer;
    }
    .supplier-option-cell.selected {
        background: #eff6ff;
        border-left: 3px solid #0f6fff;
    }
    .price-display {
        font-size: 16px;
        font-weight: 700;
        color: #0f6fff;
        margin-bottom: 4px;
    }
    .best-price-badge {
        display: inline-block;
        background: rgba(34,197,94,0.12);
        color: #16a34a;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        margin-left: 4px;
    }
</style>

<div class="quote-compare-container">
    {{-- Header Info --}}
    <div style="background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 6px rgba(2,6,23,0.06);">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
            <div>
                <div style="font-size: 12px; color: #6b7280; margin-bottom: 4px;">Quote Number</div>
                <div style="font-size: 18px; font-weight: 700; color: #0f172a;">{{ $customerQuote->quote_number }}</div>
            </div>
            <div>
                <div style="font-size: 12px; color: #6b7280; margin-bottom: 4px;">RFQ</div>
                <div style="font-size: 14px; font-weight: 600; color: #0f172a;">{{ $customerQuote->order->rfq_number ?? 'N/A' }}</div>
            </div>
            <div>
                <div style="font-size: 12px; color: #6b7280; margin-bottom: 4px;">Status</div>
                <span style="display: inline-block; padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 600;
                    background: {{ $customerQuote->status === 'sent' ? '#fef3c7' : ($customerQuote->status === 'accepted' ? '#d1fae5' : '#f3f4f6') }};
                    color: {{ $customerQuote->status === 'sent' ? '#92400e' : ($customerQuote->status === 'accepted' ? '#065f46' : '#374151') }};">
                    {{ ucfirst($customerQuote->status) }}
                </span>
            </div>
            <div>
                <div style="font-size: 12px; color: #6b7280; margin-bottom: 4px;">Expires</div>
                <div style="font-size: 14px; font-weight: 600; color: {{ $customerQuote->expires_at < now() ? '#dc2626' : '#16a34a' }};">
                    {{ $customerQuote->expires_at->format('M d, Y') }}
                </div>
            </div>
        </div>
    </div>

    {{-- Products Selection Table --}}
    <div class="products-section">
        <h2 style="font-size: 20px; font-weight: 700; color: #0f172a; margin-bottom: 20px;">
            📦 Select Products
        </h2>

        @if($allProducts->isEmpty())
            <div style="text-align: center; padding: 40px; color: #9ca3af;">
                <div style="font-size: 48px; margin-bottom: 16px;">📭</div>
                <div style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">No Products Available</div>
            </div>
        @else
            <table class="products-table">
                <thead>
                    <tr>
                        <th style="width: 30%;">Product</th>
                        @foreach($visibleItems as $item)
                            <th style="text-align: center; width: {{ 70 / $visibleItems->count() }}%;">
                                {{ $item->display_name }}
                                @if($customerQuote->show_supplier_names && $item->supplierQuote->supplier)
                                    <div style="font-size: 11px; color: #6b7280; font-weight: 400; margin-top: 2px;">
                                        {{ $item->supplierQuote->supplier->name }}
                                    </div>
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($allProducts as $productData)
                        @php
                            $product = $productData['product'];
                            $items = $productData['items'];
                            $cheapestPrice = $items->min('price');
                        @endphp
                        <tr>
                            <td class="product-name-cell">
                                {{ $product->name ?? 'Unknown Product' }}
                                @if($product->code)
                                    <div style="font-size: 11px; color: #6b7280; font-weight: 400; margin-top: 2px;">
                                        {{ $product->code }}
                                    </div>
                                @endif
                            </td>
                            
                            @foreach($visibleItems as $item)
                                @php
                                    $itemData = $items->firstWhere('supplier_quote_id', $item->supplier_quote_id);
                                    $isCheapest = $itemData && $itemData['price'] === $cheapestPrice;
                                    $isSelected = $itemData && in_array($itemData['quote_item_id'], $selectedProducts);
                                @endphp
                                
                                <td class="supplier-option-cell {{ $itemData ? 'selectable' : '' }} {{ $isSelected ? 'selected' : '' }}"
                                    @if($itemData)
                                        wire:click="toggleProduct({{ $itemData['quote_item_id'] }})"
                                    @endif
                                >
                                    @if($itemData)
                                        <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                                            <input 
                                                type="checkbox" 
                                                class="selection-checkbox"
                                                {{ $isSelected ? 'checked' : '' }}
                                                wire:click.stop="toggleProduct({{ $itemData['quote_item_id'] }})"
                                            >
                                            <div class="price-display">
                                                ${{ number_format($itemData['price'] / 100, 2) }}
                                                @if($isCheapest)
                                                    <span class="best-price-badge">⭐ Best</span>
                                                @endif
                                            </div>
                                            <div style="font-size: 11px; color: #6b7280;">
                                                Qty: {{ number_format($itemData['quantity']) }}
                                            </div>
                                            @if($itemData['lead_time_days'])
                                                <div style="font-size: 11px; color: #6b7280;">
                                                    @php
                                                        $days = $itemData['lead_time_days'];
                                                        if ($days < 7) {
                                                            echo "{$days}d";
                                                        } elseif ($days < 30) {
                                                            echo ceil($days / 7) . 'w';
                                                        } else {
                                                            echo ceil($days / 30) . 'm';
                                                        }
                                                    @endphp
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <div style="color: #9ca3af; font-size: 12px;">Not quoted</div>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
