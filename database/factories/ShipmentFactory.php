<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShipmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shipment_number' => 'SHIP-' . $this->faker->unique()->numerify('########'),
            'shipment_type' => $this->faker->randomElement(['outgoing', 'incoming']),
            'carrier' => $this->faker->randomElement(['DHL', 'FedEx', 'Maersk', 'UPS']),
            'tracking_number' => $this->faker->unique()->numerify('####################'),
            'shipping_method' => $this->faker->randomElement(['air', 'sea', 'land', 'courier']),
            'status' => $this->faker->randomElement(['draft', 'pending', 'in_transit', 'delivered']),
        ];
    }
}
