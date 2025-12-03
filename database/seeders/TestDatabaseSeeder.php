<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

/**
 * TestDatabaseSeeder
 * 
 * Seeder específico para testes que cria apenas os dados mínimos necessários.
 * Evita conflitos de constraint violation durante testes.
 */
class TestDatabaseSeeder extends Seeder
{
    /**
     * Seed the test database with minimal data
     */
    public function run(): void
    {
        // Only create currencies for tests
        $this->createCurrencies();
        $this->command->info('✓ Created currencies for testing');
    }

    /**
     * Create currencies for testing
     */
    private function createCurrencies(): void
    {
        $currencies = [
            ['code' => 'USD', 'name' => 'US Dollar', 'name_plural' => 'US Dollars', 'symbol' => '$'],
            ['code' => 'EUR', 'name' => 'Euro', 'name_plural' => 'Euros', 'symbol' => '€'],
            ['code' => 'GBP', 'name' => 'British Pound', 'name_plural' => 'British Pounds', 'symbol' => '£'],
            ['code' => 'CNY', 'name' => 'Chinese Yuan', 'name_plural' => 'Chinese Yuan', 'symbol' => '¥'],
            ['code' => 'BRL', 'name' => 'Brazilian Real', 'name_plural' => 'Brazilian Reais', 'symbol' => 'R$'],
        ];

        foreach ($currencies as $currency) {
            Currency::firstOrCreate(
                ['code' => $currency['code']],
                $currency
            );
        }
    }
}
