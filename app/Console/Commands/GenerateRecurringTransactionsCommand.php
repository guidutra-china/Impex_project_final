<?php

namespace App\Console\Commands;

use App\Models\RecurringTransaction;
use Illuminate\Console\Command;

class GenerateRecurringTransactionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:generate-recurring
                            {--dry-run : Show what would be generated without actually creating transactions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate financial transactions from recurring transaction templates';

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

        // Get all recurring transactions ready to generate
        $recurringTransactions = RecurringTransaction::readyToGenerate()->get();

        if ($recurringTransactions->isEmpty()) {
            $this->info('✅ No recurring transactions to generate at this time.');
            return Command::SUCCESS;
        }

        $this->info("📋 Found {$recurringTransactions->count()} recurring transaction(s) ready to generate:");
        $this->newLine();

        $generated = 0;
        $failed = 0;

        foreach ($recurringTransactions as $recurring) {
            try {
                $this->line("  • {$recurring->name}");
                $this->line("    Type: " . strtoupper($recurring->type));
                $this->line("    Amount: {$recurring->currency->symbol} " . number_format($recurring->amount / 100, 2));
                $this->line("    Due Date: {$recurring->next_due_date->format('d/m/Y')}");

                if (!$isDryRun) {
                    $transaction = $recurring->generateTransaction();
                    $this->line("    ✅ Generated: {$transaction->transaction_number}");
                    $generated++;
                } else {
                    $this->line("    ⚠️  Would generate transaction");
                }

                $this->newLine();
            } catch (\Exception $e) {
                $this->error("    ❌ Failed: {$e->getMessage()}");
                $this->newLine();
                $failed++;
            }
        }

        // Summary
        $this->newLine();
        if ($isDryRun) {
            $this->info("📊 Summary (Dry Run):");
            $this->line("   Would generate: {$recurringTransactions->count()} transaction(s)");
        } else {
            $this->info("📊 Summary:");
            $this->line("   ✅ Generated: {$generated} transaction(s)");
            if ($failed > 0) {
                $this->line("   ❌ Failed: {$failed} transaction(s)");
            }
        }

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
