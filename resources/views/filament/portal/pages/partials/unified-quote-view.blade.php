{{-- Unified Quote View: Simple product list without supplier information --}}

<style>
    .unified-quote-container {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1px 6px rgba(0,0,0,0.08);
    }
    .unified-product-list {
        display: grid;
        gap: 16px;
    }
    .unified-product-item {
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 20px;
        transition: all 0.2s ease;
    }
    .unified-product-item:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    .unified-product-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 16px;
        padding-bottom: 16px;
        border-bottom: 2px solid #f1f5f9;
    }
    .unified-product-name {
        font-size: 18px;
        font-weight: 700;
        color: #0f172a;
    }
    .unified-product-code {
        font-size: 13px;
        color: #64748b;
        margin-top: 4px;
    }
    .unified-product-price {
        font-size: 32px;
        font-weight: 800;
        color: #0f6fff;
    }
    .unified-product-specs {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 12px;
    }
    .unified-spec-item {
        display: flex;
        flex-direction: column;
    }
    .unified-spec-label {
        font-size: 12px;
        color: #6b7280;
        text-transform: uppercase;
        font-weight: 600;
        margin-bottom: 4px;
    }
    .unified-spec-value {
        font-size: 14px;
        color: #0f172a;
        font-weight: 500;
    }
    .unified-total-section {
        margin-top: 24px;
        padding-top: 24px;
        border-top: 2px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .unified-total-label {
        font-size: 18px;
        font-weight: 600;
        color: #64748b;
    }
    .unified-total-price {
        font-size: 36px;
        font-weight: 800;
        color: #16a34a;
    }
</style>

<div class="unified-quote-container">
    <h2 style="font-size: 24px; font-weight: 700; color: #0f172a; margin-bottom: 24px;">
        📦 Products & Pricing
    </h2>

    @php
        // Collect all products from visible items
        $allProducts = collect();
        $totalPrice = 0;
        
        foreach($items as $customerQuoteItem) {
            $supplierQuote = $customerQuoteItem->supplierQuote;
            if($supplierQuote && $supplierQuote->items) {
                foreach($supplierQuote->items as $quoteItem) {
                    $productId = $quoteItem->product_id;
                    if(!$allProducts->has($productId)) {
                        $allProducts->put($productId, $quoteItem);
                        $totalPrice += $quoteItem->unit_price_after_commission * $quoteItem->quantity;
                    }
                }
            }
        }
    @endphp

    <div class="unified-product-list">
        @foreach($allProducts as $quoteItem)
            @php
                $product = $quoteItem->product;
                $itemTotal = $quoteItem->unit_price_after_commission * $quoteItem->quantity;
            @endphp
            
            <div class="unified-product-item">
                <div class="unified-product-header">
                    <div>
                        <div class="unified-product-name">{{ $product->name ?? 'Product' }}</div>
                        @if($product->code)
                            <div class="unified-product-code">Code: {{ $product->code }}</div>
                        @endif
                    </div>
                    <div class="unified-product-price">
                        ${{ number_format($quoteItem->unit_price_after_commission / 100, 2) }}
                    </div>
                </div>

                <div class="unified-product-specs">
                    <div class="unified-spec-item">
                        <div class="unified-spec-label">Quantity</div>
                        <div class="unified-spec-value">{{ number_format($quoteItem->quantity) }} {{ $product->unit ?? 'pcs' }}</div>
                    </div>

                    @if($quoteItem->lead_time_days)
                        <div class="unified-spec-item">
                            <div class="unified-spec-label">Delivery Time</div>
                            <div class="unified-spec-value">
                                @php
                                    $days = $quoteItem->lead_time_days;
                                    if ($days < 7) {
                                        echo "{$days} days";
                                    } elseif ($days < 30) {
                                        $weeks = ceil($days / 7);
                                        echo "{$weeks} " . ($weeks === 1 ? 'week' : 'weeks');
                                    } else {
                                        $months = ceil($days / 30);
                                        echo "{$months} " . ($months === 1 ? 'month' : 'months');
                                    }
                                @endphp
                            </div>
                        </div>
                    @endif

                    <div class="unified-spec-item">
                        <div class="unified-spec-label">Subtotal</div>
                        <div class="unified-spec-value" style="font-weight: 700; color: #16a34a;">
                            ${{ number_format($itemTotal / 100, 2) }}
                        </div>
                    </div>

                    @if($product->description)
                        <div class="unified-spec-item" style="grid-column: 1 / -1;">
                            <div class="unified-spec-label">Description</div>
                            <div class="unified-spec-value" style="color: #64748b;">{{ $product->description }}</div>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="unified-total-section">
        <div class="unified-total-label">Total Quote Value</div>
        <div class="unified-total-price">${{ number_format($totalPrice / 100, 2) }}</div>
    </div>

    @if($record->customer_notes)
        <div style="margin-top: 24px; padding: 16px; background: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 8px;">
            <div style="font-size: 14px; font-weight: 600; color: #92400e; margin-bottom: 8px;">📝 Notes</div>
            <div style="font-size: 14px; color: #78350f;">{{ $record->customer_notes }}</div>
        </div>
    @endif
</div>
