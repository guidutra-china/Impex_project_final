<?php

namespace App\Observers;

use App\Models\Client;
use Illuminate\Support\Str;

class ClientObserver
{
    /**
     * Handle the Client "creating" event.
     */
    public function creating(Client $client): void
    {
        // Only generate code if not provided
        if (empty($client->code)) {
            $client->code = $this->generateUniqueCode($client->name);
        }
    }

    /**
     * Handle the Client "updating" event.
     */
    public function updating(Client $client): void
    {
        // If name changed and code is empty, regenerate
        if ($client->isDirty('name') && empty($client->code)) {
            $client->code = $this->generateUniqueCode($client->name);
        }
    }

    /**
     * Generate a unique 5-letter code from company name
     *
     * @param string $name
     * @return string
     */
    protected function generateUniqueCode(string $name): string
    {
        // Strategy 1: First 5 letters of first word
        $code = $this->extractLetters($name, 0, 5);
        
        if ($this->isCodeAvailable($code)) {
            return $code;
        }

        // Strategy 2: Try combining first word + second word
        $words = $this->extractWords($name);
        
        if (count($words) >= 2) {
            // Try first 3 letters of first word + first 2 of second word
            $code = $this->extractLetters($words[0], 0, 3) . $this->extractLetters($words[1], 0, 2);
            
            if (strlen($code) === 5 && $this->isCodeAvailable($code)) {
                return $code;
            }
            
            // Try first 2 letters of first word + first 3 of second word
            $code = $this->extractLetters($words[0], 0, 2) . $this->extractLetters($words[1], 0, 3);
            
            if (strlen($code) === 5 && $this->isCodeAvailable($code)) {
                return $code;
            }
            
            // Try first 5 letters of second word
            $code = $this->extractLetters($words[1], 0, 5);
            
            if ($this->isCodeAvailable($code)) {
                return $code;
            }
        }

        // Strategy 3: Add sequential numbers
        $baseCode = $this->extractLetters($name, 0, 4);
        
        for ($i = 1; $i <= 99; $i++) {
            $code = $baseCode . $i;
            
            if (strlen($code) === 5 && $this->isCodeAvailable($code)) {
                return $code;
            }
        }

        // Fallback: Random code
        return $this->generateRandomCode();
    }

    /**
     * Extract letters from text
     *
     * @param string $text
     * @param int $start
     * @param int $length
     * @return string
     */
    protected function extractLetters(string $text, int $start, int $length): string
    {
        // Remove non-alphabetic characters and convert to uppercase
        $letters = strtoupper(preg_replace('/[^A-Za-z]/', '', $text));
        
        return substr($letters, $start, $length);
    }

    /**
     * Extract words from company name
     *
     * @param string $name
     * @return array
     */
    protected function extractWords(string $name): array
    {
        // Split by spaces and filter out common words
        $commonWords = ['inc', 'ltd', 'llc', 'corp', 'co', 'company', 'limited', 'corporation'];
        
        $words = preg_split('/\s+/', strtolower($name));
        
        return array_values(array_filter($words, function($word) use ($commonWords) {
            return !in_array($word, $commonWords) && strlen($word) > 0;
        }));
    }

    /**
     * Check if code is available
     *
     * @param string $code
     * @return bool
     */
    protected function isCodeAvailable(string $code): bool
    {
        if (strlen($code) !== 5) {
            return false;
        }

        return !Client::where('code', $code)->exists();
    }

    /**
     * Generate random 5-letter code
     *
     * @return string
     */
    protected function generateRandomCode(): string
    {
        do {
            $code = strtoupper(Str::random(5));
            $code = preg_replace('/[^A-Z]/', '', $code);
        } while (strlen($code) !== 5 || !$this->isCodeAvailable($code));

        return $code;
    }
}
