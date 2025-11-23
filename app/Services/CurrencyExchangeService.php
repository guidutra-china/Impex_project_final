<?php

namespace App\Services;

use App\Models\Currency;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class CurrencyExchangeService
{
    /**
     * Base URL for ExchangeRate-API
     */
    private const API_BASE_URL = 'https://v6.exchangerate-api.com/v6';

    /**
     * Get the API key from environment
     */
    private function getApiKey(): string
    {
        $apiKey = config('services.exchangerate.api_key');
        
        if (empty($apiKey)) {
            throw new Exception('ExchangeRate API key not configured. Please set EXCHANGERATE_API_KEY in your .env file');
        }
        
        return $apiKey;
    }

    /**
     * Get the base currency (the currency with is_base = true)
     */
    private function getBaseCurrency(): Currency
    {
        $baseCurrency = Currency::where('is_base', true)->first();
        
        if (!$baseCurrency) {
            throw new Exception('No base currency configured. Please set one currency as base.');
        }
        
        return $baseCurrency;
    }

    /**
     * Fetch exchange rates from the API
     *
     * @param string $baseCurrencyCode The base currency code (e.g., 'USD')
     * @return array The conversion rates
     * @throws Exception
     */
    public function fetchExchangeRates(string $baseCurrencyCode): array
    {
        $apiKey = $this->getApiKey();
        $url = self::API_BASE_URL . "/{$apiKey}/latest/{$baseCurrencyCode}";

        try {
            $response = Http::timeout(10)->get($url);

            if (!$response->successful()) {
                throw new Exception("API request failed with status: {$response->status()}");
            }

            $data = $response->json();

            if ($data['result'] !== 'success') {
                $errorType = $data['error-type'] ?? 'unknown';
                throw new Exception("API returned error: {$errorType}");
            }

            return $data['conversion_rates'];
        } catch (Exception $e) {
            Log::error('Failed to fetch exchange rates', [
                'base_currency' => $baseCurrencyCode,
                'error' => $e->getMessage(),
            ]);
            
            throw new Exception("Failed to fetch exchange rates: {$e->getMessage()}");
        }
    }

    /**
     * Update all currency exchange rates based on the base currency
     *
     * @return array Statistics about the update (updated, failed, skipped)
     */
    public function updateAllRates(): array
    {
        $baseCurrency = $this->getBaseCurrency();
        $rates = $this->fetchExchangeRates($baseCurrency->code);

        $stats = [
            'updated' => 0,
            'failed' => 0,
            'skipped' => 0,
            'base_currency' => $baseCurrency->code,
            'timestamp' => now()->toDateTimeString(),
        ];

        // Get all active currencies
        $currencies = Currency::where('is_active', true)->get();

        foreach ($currencies as $currency) {
            // Skip the base currency (rate is always 1.0)
            if ($currency->is_base) {
                $stats['skipped']++;
                continue;
            }

            // Check if we have a rate for this currency
            if (!isset($rates[$currency->code])) {
                Log::warning("No exchange rate found for currency: {$currency->code}");
                $stats['failed']++;
                continue;
            }

            try {
                $currency->exchange_rate = $rates[$currency->code];
                $currency->save();
                $stats['updated']++;

                Log::info("Updated exchange rate for {$currency->code}: {$rates[$currency->code]}");
            } catch (Exception $e) {
                Log::error("Failed to update currency {$currency->code}", [
                    'error' => $e->getMessage(),
                ]);
                $stats['failed']++;
            }
        }

        return $stats;
    }

    /**
     * Update exchange rate for a specific currency
     *
     * @param Currency $currency
     * @return bool
     */
    public function updateCurrencyRate(Currency $currency): bool
    {
        if ($currency->is_base) {
            Log::info("Skipping base currency: {$currency->code}");
            return false;
        }

        $baseCurrency = $this->getBaseCurrency();
        $rates = $this->fetchExchangeRates($baseCurrency->code);

        if (!isset($rates[$currency->code])) {
            throw new Exception("No exchange rate found for currency: {$currency->code}");
        }

        $currency->exchange_rate = $rates[$currency->code];
        $currency->save();

        Log::info("Updated exchange rate for {$currency->code}: {$rates[$currency->code]}");

        return true;
    }

    /**
     * Get available currencies from the API
     *
     * @return array List of currency codes
     */
    public function getAvailableCurrencies(): array
    {
        $baseCurrency = $this->getBaseCurrency();
        $rates = $this->fetchExchangeRates($baseCurrency->code);

        return array_keys($rates);
    }

    /**
     * Convert an amount from one currency to another
     *
     * @param float $amount
     * @param string $fromCurrency
     * @param string $toCurrency
     * @return float
     */
    public function convert(float $amount, string $fromCurrency, string $toCurrency): float
    {
        $rates = $this->fetchExchangeRates($fromCurrency);

        if (!isset($rates[$toCurrency])) {
            throw new Exception("No exchange rate found for {$fromCurrency} to {$toCurrency}");
        }

        return $amount * $rates[$toCurrency];
    }
}
