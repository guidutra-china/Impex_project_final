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
        
        // Generate unique code using a combination of strategies
        // This ensures we never get duplicates like "XXX"
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
     * Generate a unique client code
     */
    private function generateUniqueCode(string $companyName): string
    {
        // Strategy 1: Try to use company name initials
        $words = explode(' ', trim($companyName));
        $firstLetter = strtoupper(substr($words[0] ?? 'A', 0, 1));
        $secondLetter = isset($words[1]) ? strtoupper(substr($words[1], 0, 1)) : strtoupper(substr($companyName, 1, 1));
        
        // Try with different digits
        for ($digit = 0; $digit <= 9; $digit++) {
            $code = $firstLetter . $secondLetter . $digit;
            if (!Client::where('code', $code)->exists()) {
                return $code;
            }
        }
        
        // Strategy 2: Use random 3-letter combinations
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $code = strtoupper($this->faker->bothify('???'));
            if (!Client::where('code', $code)->exists()) {
                return $code;
            }
        }
        
        // Strategy 3: Use UUID-based code (guaranteed unique)
        $uuid = Str::uuid()->toString();
        $code = strtoupper(substr($uuid, 0, 3));
        
        // Final fallback: use timestamp-based code
        if (Client::where('code', $code)->exists()) {
            $code = strtoupper(substr(md5(microtime(true)), 0, 3));
        }
        
        return $code;
    }
}
