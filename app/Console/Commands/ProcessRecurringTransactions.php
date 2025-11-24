<?php

namespace App\Console\Commands;

use App\Models\RecurringTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessRecurringTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recurring:process
                            {--dry-run : Show what would be processed without actually creating transactions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process recurring transactions that are due today';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No transactions will be created');
            $this->newLine();
        }

        // Find all active recurring transactions that are due today or overdue
        $dueRecurrences = RecurringTransaction::query()
            ->where('is_active', true)
            ->whereDate('next_due_date', '<=', now())
            ->get();

        if ($dueRecurrences->isEmpty()) {
            $this->info('✅ No recurring transactions are due today.');
            return self::SUCCESS;
        }

        $this->info("Found {$dueRecurrences->count()} recurring transaction(s) to process:");
        $this->newLine();

        $successCount = 0;
        $errorCount = 0;

        foreach ($dueRecurrences as $recurring) {
            $this->line("📋 Processing: {$recurring->name}");
            $this->line("   Type: {$recurring->type}");
            $this->line("   Amount: " . money($recurring->amount, $recurring->currency->code));
            $this->line("   Due Date: {$recurring->next_due_date->format('Y-m-d')}");

            if ($isDryRun) {
                $this->line("   ⚠️  Would generate transaction (dry run)");
                $successCount++;
            } else {
                try {
                    $transaction = $recurring->generateTransaction();
                    
                    $this->line("   ✅ Generated: {$transaction->transaction_number}");
                    $this->line("   📅 Next due date: {$recurring->fresh()->next_due_date->format('Y-m-d')}");
                    
                    $successCount++;
                    
                    // Log success
                    Log::info("Recurring transaction processed", [
                        'recurring_id' => $recurring->id,
                        'recurring_name' => $recurring->name,
                        'transaction_id' => $transaction->id,
                        'transaction_number' => $transaction->transaction_number,
                        'next_due_date' => $recurring->fresh()->next_due_date->format('Y-m-d'),
                    ]);
                    
                } catch (\Exception $e) {
                    $this->error("   ❌ Error: {$e->getMessage()}");
                    $errorCount++;
                    
                    // Log error
                    Log::error("Failed to process recurring transaction", [
                        'recurring_id' => $recurring->id,
                        'recurring_name' => $recurring->name,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            $this->newLine();
        }

        // Summary
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info("📊 Summary:");
        $this->info("   ✅ Successful: {$successCount}");
        
        if ($errorCount > 0) {
            $this->error("   ❌ Failed: {$errorCount}");
        }
        
        if ($isDryRun) {
            $this->warn("   ⚠️  This was a DRY RUN - no transactions were created");
        }
        
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        return $errorCount > 0 ? self::FAILURE : self::SUCCESS;
    }
}
