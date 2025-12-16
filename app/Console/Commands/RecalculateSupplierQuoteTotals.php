<?php

namespace App\Console\Commands;

use App\Models\SupplierQuote;
use Illuminate\Console\Command;

class RecalculateSupplierQuoteTotals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quotes:recalculate {--id=* : Specific quote IDs to recalculate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate total prices for supplier quotes based on their items';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $quoteIds = $this->option('id');

        if (!empty($quoteIds)) {
            $quotes = SupplierQuote::withoutGlobalScopes()
                ->whereIn('id', $quoteIds)
                ->with('items')
                ->get();
            
            $this->info("Recalculating " . $quotes->count() . " specific supplier quotes...");
        } else {
            $quotes = SupplierQuote::withoutGlobalScopes()
                ->with('items')
                ->get();
            
            $this->info("Recalculating ALL supplier quotes (" . $quotes->count() . " total)...");
        }

        $bar = $this->output->createProgressBar($quotes->count());
        $bar->start();

        $updated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($quotes as $quote) {
            try {
                // Check if quote has items
                if ($quote->items->isEmpty()) {
                    $this->newLine();
                    $this->warn("  Quote #{$quote->id} has no items, skipping...");
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                // Store old values for comparison
                $oldTotalBefore = $quote->total_price_before_commission;
                $oldTotalAfter = $quote->total_price_after_commission;

                // Recalculate
                $quote->calculateCommission();

                // Check if values changed
                if ($oldTotalBefore != $quote->total_price_before_commission || 
                    $oldTotalAfter != $quote->total_price_after_commission) {
                    $updated++;
                    $this->newLine();
                    $this->info("  Quote #{$quote->id}: {$oldTotalBefore} → {$quote->total_price_before_commission}");
                } else {
                    $skipped++;
                }

            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("  Quote #{$quote->id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info("✅ Recalculation complete!");
        $this->table(
            ['Status', 'Count'],
            [
                ['Updated', $updated],
                ['Skipped (no change or no items)', $skipped],
                ['Errors', $errors],
                ['Total processed', $quotes->count()],
            ]
        );

        return Command::SUCCESS;
    }
}
