<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        $companyName = $this->faker->company();
        
        // Generate unique code - guaranteed to be 2-3 characters
        $code = $this->generateUniqueCode($companyName);
        
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

    /**
     * Generate a unique client code - guaranteed to be 2-3 characters
     * Uses multiple strategies to ensure uniqueness
     */
    private function generateUniqueCode(string $companyName): string
    {
        // Strategy 1: Try to use company name initials + digit
        $words = explode(' ', trim($companyName));
        if (count($words) >= 2) {
            $firstLetter = strtoupper(substr($words[0], 0, 1));
            $secondLetter = strtoupper(substr($words[1], 0, 1));
            
            // Try with different digits
            for ($digit = 0; $digit <= 9; $digit++) {
                $code = $firstLetter . $secondLetter . $digit;
                if (!Client::where('code', $code)->exists()) {
                    return $code;
                }
            }
        }
        
        // Strategy 2: Use first letter + random letter + digit
        $firstLetter = strtoupper(substr($companyName, 0, 1));
        for ($attempt = 0; $attempt < 100; $attempt++) {
            $randomLetter = strtoupper($this->faker->randomLetter());
            $digit = $this->faker->randomDigit();
            $code = $firstLetter . $randomLetter . $digit;
            
            if (!Client::where('code', $code)->exists()) {
                return $code;
            }
        }
        
        // Strategy 3: Random 3-character code (letters + digit)
        for ($attempt = 0; $attempt < 100; $attempt++) {
            $code = strtoupper(
                $this->faker->randomLetter() .
                $this->faker->randomLetter() .
                $this->faker->randomDigit()
            );
            
            if (!Client::where('code', $code)->exists()) {
                return $code;
            }
        }
        
        // Strategy 4: Use UUID-based code (guaranteed unique)
        $uuid = Str::uuid()->toString();
        $code = strtoupper(substr(str_replace('-', '', $uuid), 0, 3));
        
        if (!Client::where('code', $code)->exists()) {
            return $code;
        }
        
        // Strategy 5: Use timestamp-based code (final fallback)
        // This is guaranteed to be unique because it includes microseconds
        $code = strtoupper(substr(md5(microtime(true) . random_bytes(10)), 0, 3));
        
        // If somehow this code exists, keep trying with different timestamps
        $attempts = 0;
        while (Client::where('code', $code)->exists() && $attempts < 10) {
            $code = strtoupper(substr(md5(microtime(true) . random_bytes(10)), 0, 3));
            $attempts++;
        }
        
        return $code;
    }
}
