<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShipmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            // Let the model generate shipment_number automatically via boot()
            // This prevents duplicate key errors from race conditions
            'shipment_type' => $this->faker->randomElement(['outbound', 'inbound']),
            'carrier' => $this->faker->randomElement(['DHL', 'FedEx', 'Maersk', 'UPS']),
            'tracking_number' => $this->faker->unique()->numerify('####################'),
            'shipping_method' => $this->faker->randomElement(['air', 'sea', 'land', 'courier']),
            'status' => $this->faker->randomElement(['draft', 'pending', 'in_transit', 'delivered']),
            'origin_address' => $this->faker->address(),
            'destination_address' => $this->faker->address(),
            'shipment_date' => $this->faker->dateTime(),
            'shipping_cost' => $this->faker->numberBetween(100, 10000),
            'insurance_cost' => $this->faker->numberBetween(0, 1000),
            'currency_id' => 1, // Default currency
        ];
    }
}
