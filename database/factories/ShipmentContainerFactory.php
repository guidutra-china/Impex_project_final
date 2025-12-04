<?php

namespace Database\Factories;

use App\Models\Shipment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShipmentContainerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shipment_id' => Shipment::factory(),
            'container_number' => 'MSCU' . $this->faker->unique()->numerify('########'),
            'container_type' => $this->faker->randomElement(['20ft', '40ft', '40hc']),
            'max_weight' => $this->faker->numberBetween(20000, 30000),
            'max_volume' => $this->faker->randomFloat(2, 30, 35),
            'current_weight' => 0,
            'current_volume' => 0,
            'status' => 'draft',
            'created_by' => User::factory(),
        ];
    }
}
