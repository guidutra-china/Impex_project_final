<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Seeds common currencies with exchange rates relative to USD
     * Maximum 5 currencies for development environment
     * Exchange rates are approximate and should be updated regularly
     */
    public function run(): void
    {
        // Maximum 5 currencies for development
        $currencies = [
            [
                'code' => 'USD',
                'name' => 'US Dollar',
                'name_plural' => 'US Dollars',
                'symbol' => '$',
                'is_base' => true,
                'is_active' => true,
            ],
            [
                'code' => 'EUR',
                'name' => 'Euro',
                'name_plural' => 'Euros',
                'symbol' => '€',
                'is_base' => false,
                'is_active' => true,
            ],
            [
                'code' => 'BRL',
                'name' => 'Brazilian Real',
                'name_plural' => 'Brazilian Reais',
                'symbol' => 'R$',
                'is_base' => false,
                'is_active' => true,
            ],
            [
                'code' => 'GBP',
                'name' => 'British Pound',
                'name_plural' => 'British Pounds',
                'symbol' => '£',
                'is_base' => false,
                'is_active' => true,
            ],
            [
                'code' => 'CNY',
                'name' => 'Chinese Yuan',
                'name_plural' => 'Chinese Yuan',
                'symbol' => '¥',
                'is_base' => false,
                'is_active' => true,
            ],

        ];

        foreach ($currencies as $currency) {
            Currency::updateOrCreate(
                ['code' => $currency['code']],
                $currency
            );
        }

        $this->command->info('Currencies seeded successfully!');
        $this->command->info('Note: Exchange rates are approximate. Please update them with current rates.');
    }
}

