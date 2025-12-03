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
        
        // Generate unique 2-3 letter code
        $attempts = 0;
        $code = null;
        
        while ($attempts < 20) {
            // Try different strategies to generate unique codes
            if ($attempts < 5) {
                // First try: use company name initials + random digit
                $words = explode(' ', $companyName);
                $firstLetter = strtoupper(substr($words[0] ?? 'A', 0, 1));
                $secondLetter = isset($words[1]) ? strtoupper(substr($words[1], 0, 1)) : strtoupper(substr($companyName, 1, 1));
                $digit = $this->faker->randomDigit();
                $code = $firstLetter . $secondLetter . $digit;
            } else {
                // Fallback: random 3-letter code
                $code = strtoupper($this->faker->bothify('???'));
            }
            
            // Ensure code is not empty and is unique
            if (!empty($code) && !Client::where('code', $code)->exists()) {
                break;
            }
            
            $attempts++;
        }
        
        // If we still don't have a code, use a timestamp-based one
        if (empty($code) || Client::where('code', $code)->exists()) {
            $code = strtoupper(substr(md5(microtime()), 0, 3));
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
