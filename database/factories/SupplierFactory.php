<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'email' => $this->faker->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'country' => $this->faker->randomElement(['China', 'USA', 'Brazil', 'Germany', 'Japan']),
            'postal_code' => $this->faker->postcode(),
            'tax_id' => $this->faker->numerify('##-#######'),
            'website' => $this->faker->optional()->domainName(),
            'payment_terms' => $this->faker->randomElement(['Net 30', 'Net 60', 'Net 90', '50% Advance, 50% on Delivery']),
            'lead_time_days' => $this->faker->numberBetween(7, 90),
            'minimum_order_value' => $this->faker->numberBetween(100, 10000) * 100, // in cents
            'rating' => $this->faker->optional()->randomFloat(1, 1, 5),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
