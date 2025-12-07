<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\SupplierQuote;
use App\Models\QuoteItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncQuoteCommissionType extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quotes:sync-commission-type 
                            {--order= : Sync only quotes for specific order ID}
                            {--quote= : Sync only specific quote ID}
                            {--dry-run : Show what would be changed without actually changing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync commission_type from Order to SupplierQuotes and QuoteItems';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $orderId = $this->option('order');
        $quoteId = $this->option('quote');

        $this->info('🔄 Starting commission type sync...');
        if ($isDryRun) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be saved');
        }

        // Build query
        $query = SupplierQuote::with(['order', 'items']);
        
        if ($quoteId) {
            $query->where('id', $quoteId);
        } elseif ($orderId) {
            $query->where('order_id', $orderId);
        }

        $quotes = $query->get();

        if ($quotes->isEmpty()) {
            $this->error('❌ No quotes found');
            return 1;
        }

        $this->info("📊 Found {$quotes->count()} quote(s) to process");
        $this->newLine();

        $updated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($quotes as $quote) {
            $order = $quote->order;
            
            if (!$order) {
                $this->error("❌ Quote #{$quote->id}: Order not found");
                $errors++;
                continue;
            }

            $orderCommissionType = $order->commission_type ?? 'embedded';
            $currentQuoteType = $quote->commission_type ?? 'embedded';

            // Check if update is needed
            if ($currentQuoteType === $orderCommissionType) {
                $this->line("⏭️  Quote #{$quote->id} ({$quote->supplier->name}): Already correct ({$orderCommissionType})");
                $skipped++;
                continue;
            }

            $this->info("🔄 Quote #{$quote->id} ({$quote->supplier->name}):");
            $this->line("   Order: {$order->order_number}");
            $this->line("   Current: {$currentQuoteType}");
            $this->line("   Target:  {$orderCommissionType}");

            if (!$isDryRun) {
                try {
                    DB::beginTransaction();

                    // Update all quote items
                    $itemsUpdated = 0;
                    foreach ($quote->items as $item) {
                        $orderItem = $item->orderItem;
                        if ($orderItem) {
                            // Update OrderItem first (if needed)
                            if ($orderItem->commission_type !== $orderCommissionType) {
                                $orderItem->commission_type = $orderCommissionType;
                                $orderItem->save();
                            }

                            // Update QuoteItem
                            $item->commission_type = $orderCommissionType;
                            $item->commission_percent = $orderItem->commission_percent;
                            
                            // Recalculate prices based on new type
                            $item->total_price_before_commission = $item->unit_price_before_commission * $item->quantity;
                            
                            if ($orderCommissionType === 'embedded') {
                                $commissionMultiplier = 1 + ($item->commission_percent / 100);
                                $item->total_price_after_commission = (int) ($item->total_price_before_commission * $commissionMultiplier);
                                $item->unit_price_after_commission = (int) ($item->total_price_after_commission / $item->quantity);
                            } else {
                                // Separate - prices stay the same
                                $item->unit_price_after_commission = $item->unit_price_before_commission;
                                $item->total_price_after_commission = $item->total_price_before_commission;
                            }
                            
                            $item->save();
                            $itemsUpdated++;
                        }
                    }

                    // Recalculate quote totals
                    $quote->calculateCommission();

                    DB::commit();

                    $this->info("   ✅ Updated {$itemsUpdated} items and recalculated totals");
                    $updated++;

                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->error("   ❌ Error: {$e->getMessage()}");
                    $errors++;
                }
            } else {
                $this->line("   🔍 Would update {$quote->items->count()} items");
                $updated++;
            }

            $this->newLine();
        }

        // Summary
        $this->info('📊 Summary:');
        $this->line("   ✅ Updated: {$updated}");
        $this->line("   ⏭️  Skipped: {$skipped}");
        if ($errors > 0) {
            $this->line("   ❌ Errors: {$errors}");
        }

        if ($isDryRun && $updated > 0) {
            $this->newLine();
            $this->warn('⚠️  This was a DRY RUN. Run without --dry-run to apply changes.');
        }

        return $errors > 0 ? 1 : 0;
    }
}
