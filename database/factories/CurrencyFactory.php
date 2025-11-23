<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Currency>
 */
class CurrencyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Currency::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $currencies = [
            ['code' => 'USD', 'name' => 'US Dollar', 'name_plural' => 'US Dollars', 'symbol' => '$'],
            ['code' => 'EUR', 'name' => 'Euro', 'name_plural' => 'Euros', 'symbol' => '€'],
            ['code' => 'GBP', 'name' => 'British Pound', 'name_plural' => 'British Pounds', 'symbol' => '£'],
            ['code' => 'CNY', 'name' => 'Chinese Yuan', 'name_plural' => 'Chinese Yuan', 'symbol' => '¥'],
            ['code' => 'BRL', 'name' => 'Brazilian Real', 'name_plural' => 'Brazilian Reais', 'symbol' => 'R$'],
        ];

        $currency = fake()->randomElement($currencies);

        return [
            'code' => $currency['code'],
            'name' => $currency['name'],
            'name_plural' => $currency['name_plural'],
            'symbol' => $currency['symbol'],
            'exchange_rate' => fake()->randomFloat(4, 0.5, 10),
            'is_base' => false,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that this is the base currency.
     */
    public function base(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'USD',
            'name' => 'US Dollar',
            'name_plural' => 'US Dollars',
            'symbol' => '$',
            'exchange_rate' => 1.0000,
            'is_base' => true,
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the currency is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
