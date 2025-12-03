<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Currency;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'customer_id' => Client::factory(),
            'currency_id' => Currency::factory(),
            'status' => $this->faker->randomElement(['pending', 'processing', 'quoted', 'completed', 'cancelled']),
            'commission_percent' => $this->faker->randomFloat(2, 0, 10),
            'commission_type' => $this->faker->randomElement(['embedded', 'separate']),
            'incoterm' => $this->faker->randomElement(['FOB', 'CIF', 'DDP', 'EXW']),
            'incoterm_location' => $this->faker->city(),
            'customer_notes' => $this->faker->sentence(),
            'notes' => $this->faker->sentence(),
            'total_amount' => $this->faker->numberBetween(1000, 100000),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }

    /**
     * State for pending orders
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * State for processing orders
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
        ]);
    }

    /**
     * State for quoted orders
     */
    public function quoted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'quoted',
        ]);
    }

    /**
     * State for completed orders
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    /**
     * State for cancelled orders
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}
