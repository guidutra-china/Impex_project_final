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
        
        // Generate unique 2-3 letter code with timestamp to ensure uniqueness
        $code = strtoupper(substr($companyName, 0, 1) . substr($companyName, -1) . $this->faker->randomDigit());
        $code = substr($code, 0, 3); // Ensure max 3 characters
        
        // Ensure code is unique
        $attempts = 0;
        while (Client::where('code', $code)->exists() && $attempts < 10) {
            $code = strtoupper($this->faker->bothify('???'));
            $attempts++;
        }
        
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
