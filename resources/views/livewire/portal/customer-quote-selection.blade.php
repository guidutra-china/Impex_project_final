<div>
    {{-- This component wraps the customer quote view and adds selection functionality --}}
    @php
        // Check if we should show unified quote mode
        $showUnifiedMode = $customerQuote->show_as_unified_quote ?? false;
        
        // Get visible product selections
        $productSelections = $customerQuote->productSelections()
            ->where('is_visible_to_customer', true)
            ->with(['quoteItem.product', 'quoteItem.supplierQuote.supplier'])
            ->orderBy('display_order')
            ->get();
    @endphp

    <style>
        .selection-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #0f6fff;
        }
        .submit-selection-btn {
            background: linear-gradient(135deg, #0f6fff 0%, #0ea5e9 100%);
            color: white;
            padding: 16px 32px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(15,111,255,0.3);
            transition: all 0.2s ease;
        }
        .submit-selection-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(15,111,255,0.4);
        }
        .submit-selection-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .selection-summary {
            background: #f0f9ff;
            border: 2px solid #0ea5e9;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>

    @if($productSelections->isNotEmpty())
        <div class="selection-summary">
            <div>
                <div style="font-size: 14px; color: #0369a1; font-weight: 600;">
                    {{ count($selectedProducts) }} product(s) selected
                </div>
                <div style="font-size: 12px; color: #64748b; margin-top: 4px;">
                    Select the products you want to order
                </div>
            </div>
            <button 
                wire:click="submitSelection" 
                wire:loading.attr="disabled"
                class="submit-selection-btn"
                {{ count($selectedProducts) === 0 ? 'disabled' : '' }}
            >
                <span wire:loading.remove wire:target="submitSelection">
                    ✓ Submit Selection
                </span>
                <span wire:loading wire:target="submitSelection">
                    Processing...
                </span>
            </button>
        </div>
    @endif

    @if($showUnifiedMode)
        @include('livewire.portal.partials.unified-mode-selection', [
            'productSelections' => $productSelections,
            'selectedProducts' => $selectedProducts,
        ])
    @else
        @include('livewire.portal.partials.comparison-mode-selection', [
            'customerQuote' => $customerQuote,
            'productSelections' => $productSelections,
            'selectedProducts' => $selectedProducts,
        ])
    @endif
</div>
