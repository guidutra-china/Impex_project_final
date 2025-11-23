<?php

namespace App\Console\Commands;

use App\Services\CurrencyExchangeService;
use Illuminate\Console\Command;
use Exception;

class UpdateCurrencyRates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'currency:update-rates
                            {--force : Force update even if recently updated}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update currency exchange rates from ExchangeRate-API';

    /**
     * Execute the console command.
     */
    public function handle(CurrencyExchangeService $exchangeService): int
    {
        $this->info('🔄 Updating currency exchange rates...');
        $this->newLine();

        try {
            $stats = $exchangeService->updateAllRates();

            // Display results
            $this->info('✅ Currency rates updated successfully!');
            $this->newLine();

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Base Currency', $stats['base_currency']],
                    ['Updated', $stats['updated']],
                    ['Skipped', $stats['skipped']],
                    ['Failed', $stats['failed']],
                    ['Timestamp', $stats['timestamp']],
                ]
            );

            if ($stats['failed'] > 0) {
                $this->warn("⚠️  {$stats['failed']} currency(ies) failed to update. Check logs for details.");
                return self::FAILURE;
            }

            $this->info("💰 {$stats['updated']} currency rate(s) updated successfully!");
            
            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error('❌ Failed to update currency rates:');
            $this->error($e->getMessage());
            $this->newLine();
            $this->warn('💡 Tips:');
            $this->line('  - Check if EXCHANGERATE_API_KEY is set in your .env file');
            $this->line('  - Verify your API key is valid at https://www.exchangerate-api.com/');
            $this->line('  - Ensure you have a base currency set (is_base = true)');
            $this->line('  - Check your internet connection');
            
            return self::FAILURE;
        }
    }
}
