{{-- Unified Mode with Product Selection Checkboxes --}}

<style>
    .unified-quote-container {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1 px 6px rgba(0,0,0,0.08);
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
        position: relative;
    }
    .unified-product-item.selected {
        border-color: #0f6fff;
        background: linear-gradient(135deg, #eff6ff 0%, #ffffff 100%);
        box-shadow: 0 0 0 2px rgba(15,111,255,0.1);
    }
    .unified-product-item:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    .product-checkbox-container {
        position: absolute;
        top: 20px;
        right: 20px;
    }
</style>

<div class="unified-quote-container">
    <h2 style="font-size: 24px; font-weight: 700; color: #0f172a; margin-bottom: 24px;">
        📦 Select Products
    </h2>

    @php
        $totalPrice = 0;
    @endphp

    @if($productSelections->isEmpty())
        <div style="text-align: center; padding: 40px; color: #9ca3af;">
            <div style="font-size: 48px; margin-bottom: 16px;">📭</div>
            <div style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">No Products Available</div>
            <div style="font-size: 14px;">Please contact us for more information.</div>
        </div>
    @else
        <div class="unified-product-list">
            @foreach($productSelections as $selection)
                @php
                    $quoteItem = $selection->quoteItem;
                    $product = $quoteItem->product;
                    $itemTotal = $quoteItem->unit_price_after_commission * $quoteItem->quantity;
                    $isSelected = in_array($quoteItem->id, $selectedProducts);
                    if ($isSelected) {
                        $totalPrice += $itemTotal;
                    }
                @endphp
                
                <div class="unified-product-item {{ $isSelected ? 'selected' : '' }}" 
                     wire:click="toggleProduct({{ $quoteItem->id }})"
                     style="cursor: pointer;">
                    
                    <div class="product-checkbox-container">
                        <input 
                            type="checkbox" 
                            class="selection-checkbox"
                            {{ $isSelected ? 'checked' : '' }}
                            wire:click.stop="toggleProduct({{ $quoteItem->id }})"
                        >
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 2px solid #f1f5f9; padding-right: 50px;">
                        <div>
                            <div style="font-size: 18px; font-weight: 700; color: #0f172a;">
                                {{ $product->name ?? 'Product' }}
                            </div>
                            @if($product->code)
                                <div style="font-size: 13px; color: #64748b; margin-top: 4px;">
                                    Code: {{ $product->code }}
                                </div>
                            @endif
                        </div>
                        <div style="font-size: 32px; font-weight: 800; color: #0f6fff;">
                            ${{ number_format($quoteItem->unit_price_after_commission / 100, 2) }}
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
                        <div>
                            <div style="font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">
                                Quantity
                            </div>
                            <div style="font-size: 14px; color: #0f172a; font-weight: 500;">
                                {{ number_format($quoteItem->quantity) }} {{ $product->unit ?? 'pcs' }}
                            </div>
                        </div>

                        @if($quoteItem->lead_time_days)
                            <div>
                                <div style="font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">
                                    Delivery Time
                                </div>
                                <div style="font-size: 14px; color: #0f172a; font-weight: 500;">
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

                        <div>
                            <div style="font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">
                                Subtotal
                            </div>
                            <div style="font-size: 14px; font-weight: 700; color: #16a34a;">
                                ${{ number_format($itemTotal / 100, 2) }}
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if(count($selectedProducts) > 0)
            <div style="margin-top: 24px; padding-top: 24px; border-top: 2px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 18px; font-weight: 600; color: #64748b;">
                    Total Selected
                </div>
                <div style="font-size: 36px; font-weight: 800; color: #16a34a;">
                    ${{ number_format($totalPrice / 100, 2) }}
                </div>
            </div>
        @endif
    @endif
</div>
