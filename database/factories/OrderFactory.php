<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Currency;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'customer_id' => Client::factory(),
            'currency_id' => Currency::factory(),
            'status' => $this->faker->randomElement(['draft', 'pending', 'confirmed', 'completed']),
            'commission_percent' => $this->faker->randomFloat(2, 0, 10),
            'commission_type' => $this->faker->randomElement(['percentage', 'fixed']),
            'incoterm' => $this->faker->randomElement(['FOB', 'CIF', 'DDP', 'EXW']),
            'incoterm_location' => $this->faker->city(),
            'customer_notes' => $this->faker->sentence(),
            'notes' => $this->faker->sentence(),
            'total_amount' => $this->faker->numberBetween(1000, 100000),
            'created_by' => 1,
            'updated_by' => 1,
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
     * State for confirmed orders
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
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
}
