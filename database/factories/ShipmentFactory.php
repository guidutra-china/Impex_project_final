<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShipmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shipment_number' => 'SHIP-' . $this->faker->unique()->numerify('########'),
            'client_id' => Client::factory(),
            'status' => $this->faker->randomElement(['draft', 'pending', 'in_transit', 'delivered']),
            'departure_date' => $this->faker->dateTime(),
            'arrival_date' => $this->faker->dateTime(),
            'total_weight' => $this->faker->numberBetween(1000, 50000),
            'total_volume' => $this->faker->randomFloat(2, 10, 100),
            'created_by' => User::factory(),
        ];
    }
}
