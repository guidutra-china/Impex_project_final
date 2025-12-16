<?php

namespace App\Services;

use App\Models\CustomerQuote;
use App\Models\CustomerQuoteItem;
use App\Models\Order;
use App\Models\SupplierQuote;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CustomerQuoteService
{
    /**
     * Generate a customer quote from selected supplier quotes
     *
     * @param Order $order
     * @param array $supplierQuoteIds Array of supplier quote IDs to include
     * @param array $options Additional options (display_names, expiry_days, notes, etc.)
     * @return CustomerQuote
     */
    public function generate(Order $order, array $supplierQuoteIds, array $options = []): CustomerQuote
    {
        return DB::transaction(function () use ($order, $supplierQuoteIds, $options) {
            \Log::info('CustomerQuoteService: Starting generation', [
                'order_id' => $order->id,
                'supplier_quote_ids' => $supplierQuoteIds,
            ]);

            // Create the customer quote
            $customerQuote = CustomerQuote::create([
                'order_id' => $order->id,
                'status' => 'draft',
                'expires_at' => now()->addDays($options['expiry_days'] ?? 7),
                'internal_notes' => $options['internal_notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Get supplier quotes with items (without global scopes)
            $supplierQuotes = SupplierQuote::withoutGlobalScopes()
                ->whereIn('id', $supplierQuoteIds)
                ->where('order_id', $order->id)
                ->with(['supplier', 'items', 'items.product'])
                ->get();

            \Log::info('CustomerQuoteService: Loaded supplier quotes', [
                'count' => $supplierQuotes->count(),
                'ids' => $supplierQuotes->pluck('id')->toArray(),
            ]);

            // Create customer quote items
            $displayOrder = 1;
            foreach ($supplierQuotes as $supplierQuote) {
                \Log::info('CustomerQuoteService: Creating item', [
                    'supplier_quote_id' => $supplierQuote->id,
                    'display_order' => $displayOrder,
                ]);

                try {
                    $item = $this->createQuoteItem(
                        $customerQuote,
                        $supplierQuote,
                        $displayOrder++,
                        $options['display_names'][$supplierQuote->id] ?? null
                    );

                    \Log::info('CustomerQuoteService: Item created', [
                        'item_id' => $item->id,
                        'display_name' => $item->display_name,
                    ]);
                } catch (\Exception $e) {
                    \Log::error('CustomerQuoteService: Failed to create item', [
                        'supplier_quote_id' => $supplierQuote->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    throw $e;
                }
            }

            \Log::info('CustomerQuoteService: Finished creating items', [
                'total_items' => $displayOrder - 1,
            ]);

            return $customerQuote->fresh('items');
        });
    }

    /**
     * Create a customer quote item from a supplier quote
     *
     * @param CustomerQuote $customerQuote
     * @param SupplierQuote $supplierQuote
     * @param int $displayOrder
     * @param string|null $displayName
     * @return CustomerQuoteItem
     */
    protected function createQuoteItem(
        CustomerQuote $customerQuote,
        SupplierQuote $supplierQuote,
        int $displayOrder,
        ?string $displayName = null
    ): CustomerQuoteItem {
        $order = $customerQuote->order;
        
        // Generate display name if not provided
        if (!$displayName) {
            $displayName = $this->generateDisplayName($supplierQuote, $displayOrder);
        }

        // Calculate prices based on commission type
        $prices = $this->calculatePrices($supplierQuote, $order);

        // Extract highlights
        $highlights = $this->extractHighlights($supplierQuote);

        return CustomerQuoteItem::create([
            'customer_quote_id' => $customerQuote->id,
            'supplier_quote_id' => $supplierQuote->id,
            'display_name' => $displayName,
            'price_before_commission' => $prices['before'],
            'commission_amount' => $prices['commission'],
            'price_after_commission' => $prices['after'],
            'delivery_time' => $this->formatDeliveryTime($supplierQuote),
            'moq' => $supplierQuote->moq,
            'highlights' => $highlights,
            'display_order' => $displayOrder,
        ]);
    }

    /**
     * Generate a display name for anonymization
     *
     * @param SupplierQuote $supplierQuote
     * @param int $displayOrder
     * @return string
     */
    protected function generateDisplayName(SupplierQuote $supplierQuote, int $displayOrder): string
    {
        // Option 1: Simple alphabetic (A, B, C...)
        // return chr(64 + $displayOrder); // A=65, so 64+1=A

        // Option 2: "Option A", "Option B"...
        return 'Option ' . chr(64 + $displayOrder);

        // Option 3: Use supplier name (no anonymization)
        // return $supplierQuote->supplier->name;
    }

    /**
     * Calculate prices based on commission type
     *
     * @param SupplierQuote $supplierQuote
     * @param Order $order
     * @return array
     */
    protected function calculatePrices(SupplierQuote $supplierQuote, Order $order): array
    {
        $commissionType = $order->commission_type ?? 'embedded';
        
        if ($commissionType === 'separate') {
            // Separate: Show base price + commission separately
            return [
                'before' => $supplierQuote->total_price_before_commission,
                'commission' => $supplierQuote->commission_amount,
                'after' => $supplierQuote->total_price_after_commission,
            ];
        } else {
            // Embedded: Show only final price (commission hidden)
            return [
                'before' => $supplierQuote->total_price_after_commission,
                'commission' => 0,
                'after' => $supplierQuote->total_price_after_commission,
            ];
        }
    }

    /**
     * Extract highlights from supplier quote
     *
     * @param SupplierQuote $supplierQuote
     * @return string|null
     */
    protected function extractHighlights(SupplierQuote $supplierQuote): ?string
    {
        $highlights = [];

        // Add payment terms if available
        if ($supplierQuote->payment_terms) {
            $highlights[] = "Payment: {$supplierQuote->payment_terms}";
        }

        // Add incoterm if available
        if ($supplierQuote->incoterm) {
            $highlights[] = "Incoterm: {$supplierQuote->incoterm}";
        }

        // Add validity if available
        if ($supplierQuote->valid_until) {
            $highlights[] = "Valid until: {$supplierQuote->valid_until->format('Y-m-d')}";
        }

        return !empty($highlights) ? implode("\n", $highlights) : null;
    }

    /**
     * Format delivery time for display
     *
     * @param SupplierQuote $supplierQuote
     * @return string|null
     */
    protected function formatDeliveryTime(SupplierQuote $supplierQuote): ?string
    {
        if (!$supplierQuote->lead_time_days) {
            return null;
        }

        $days = $supplierQuote->lead_time_days;
        
        if ($days < 7) {
            return "{$days} days";
        } elseif ($days < 30) {
            $weeks = ceil($days / 7);
            return "{$weeks} " . ($weeks === 1 ? 'week' : 'weeks');
        } else {
            $months = ceil($days / 30);
            return "{$months} " . ($months === 1 ? 'month' : 'months');
        }
    }

    /**
     * Send the customer quote to the customer
     *
     * @param CustomerQuote $customerQuote
     * @return void
     */
    public function send(CustomerQuote $customerQuote): void
    {
        $customerQuote->markAsSent();

        // TODO: Send email notification to customer
        // This will be implemented in Phase 3
    }

    /**
     * Approve a customer quote item (customer selection)
     *
     * @param CustomerQuote $customerQuote
     * @param int $itemId
     * @return void
     */
    public function approveItem(CustomerQuote $customerQuote, int $itemId): void
    {
        $item = $customerQuote->items()->findOrFail($itemId);
        
        DB::transaction(function () use ($customerQuote, $item) {
            // Mark item as selected
            $item->markAsSelected();
            
            // Mark quote as approved
            $customerQuote->markAsApproved();
            
            // Update order's selected_quote_id
            $customerQuote->order->update([
                'selected_quote_id' => $item->supplier_quote_id,
            ]);
        });
    }

    /**
     * Reject a customer quote
     *
     * @param CustomerQuote $customerQuote
     * @param string|null $reason
     * @return void
     */
    public function reject(CustomerQuote $customerQuote, ?string $reason = null): void
    {
        $customerQuote->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'customer_notes' => $reason,
        ]);
    }

    /**
     * Check and expire old quotes
     *
     * @return int Number of quotes expired
     */
    public function expireOldQuotes(): int
    {
        return CustomerQuote::where('status', 'sent')
            ->where('expires_at', '<', now())
            ->update([
                'status' => 'expired',
            ]);
    }
}
