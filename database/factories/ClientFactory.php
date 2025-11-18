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
        
        // Generate 2-letter code from company name
        $words = explode(' ', $companyName);
        $code = strtoupper(substr($words[0], 0, 2));
        
        return [
            'name' => $companyName,
            'code' => $code,
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'zip' => $this->faker->postcode(),
            'country' => $this->faker->country(),
            'tax_number' => $this->faker->numerify('##-#######'),
            'website' => $this->faker->optional()->domainName(),
        ];
    }
}
