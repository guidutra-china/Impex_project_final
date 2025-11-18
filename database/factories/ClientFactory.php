<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        $companyName = $this->faker->company();
        
        // Generate 3-letter code from company name
        $words = explode(' ', $companyName);
        $code = strtoupper(substr($words[0], 0, 3));
        
        return [
            'name' => $companyName,
            'code' => $code,
            'email' => $this->faker->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'country' => $this->faker->country(),
            'postal_code' => $this->faker->postcode(),
            'tax_id' => $this->faker->numerify('##-#######'),
            'website' => $this->faker->optional()->domainName(),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
