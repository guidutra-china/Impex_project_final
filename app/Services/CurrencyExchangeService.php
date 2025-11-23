<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\ExchangeRate;
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
     * Saves historical records in exchange_rates table
     *
     * @return array Statistics about the update (updated, failed, skipped)
     */
    public function updateAllRates(): array
    {
        $baseCurrency = $this->getBaseCurrency();
        $rates = $this->fetchExchangeRates($baseCurrency->code);
        $today = today();

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
                $rate = $rates[$currency->code];

                // Create historical record in exchange_rates table
                ExchangeRate::updateOrCreate(
                    [
                        'base_currency_id' => $baseCurrency->id,
                        'target_currency_id' => $currency->id,
                        'date' => $today,
                    ],
                    [
                        'rate' => $rate,
                        'inverse_rate' => 1 / $rate,
                        'source' => 'api',
                        'source_name' => 'ExchangeRate-API',
                        'status' => 'approved', // Auto-approve API rates
                        'approved_at' => now(),
                        'notes' => 'Automatically updated from ExchangeRate-API',
                    ]
                );

                $stats['updated']++;

                Log::info("Updated exchange rate for {$currency->code}", [
                    'currency' => $currency->code,
                    'rate' => $rate,
                    'date' => $today->toDateString(),
                ]);
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
     * Saves historical record in exchange_rates table
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

        $rate = $rates[$currency->code];
        $today = today();

        // Create historical record
        ExchangeRate::updateOrCreate(
            [
                'base_currency_id' => $baseCurrency->id,
                'target_currency_id' => $currency->id,
                'date' => $today,
            ],
            [
                'rate' => $rate,
                'inverse_rate' => 1 / $rate,
                'source' => 'api',
                'source_name' => 'ExchangeRate-API',
                'status' => 'approved',
                'approved_at' => now(),
                'notes' => 'Automatically updated from ExchangeRate-API',
            ]
        );

        Log::info("Updated exchange rate for {$currency->code}: {$rate}");

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
     * Convert an amount from one currency to another using latest approved rates
     *
     * @param float $amount
     * @param string $fromCurrency Currency code
     * @param string $toCurrency Currency code
     * @param string|null $date Optional date for historical conversion
     * @return float
     * @throws Exception
     */
    public function convert(float $amount, string $fromCurrency, string $toCurrency, ?string $date = null): float
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        $fromCurrencyModel = Currency::where('code', $fromCurrency)->first();
        $toCurrencyModel = Currency::where('code', $toCurrency)->first();

        if (!$fromCurrencyModel || !$toCurrencyModel) {
            throw new Exception('Currency not found');
        }

        $rate = ExchangeRate::getConversionRate($fromCurrencyModel->id, $toCurrencyModel->id, $date);

        if (!$rate) {
            throw new Exception("No exchange rate found for {$fromCurrency} to {$toCurrency}");
        }

        return $amount * $rate;
    }

    /**
     * Get latest exchange rate for a currency pair
     *
     * @param string $fromCurrencyCode
     * @param string $toCurrencyCode
     * @param string|null $date
     * @return ExchangeRate|null
     */
    public function getLatestRate(string $fromCurrencyCode, string $toCurrencyCode, ?string $date = null): ?ExchangeRate
    {
        $fromCurrency = Currency::where('code', $fromCurrencyCode)->first();
        $toCurrency = Currency::where('code', $toCurrencyCode)->first();

        if (!$fromCurrency || !$toCurrency) {
            return null;
        }

        return ExchangeRate::getLatestRate($fromCurrency->id, $toCurrency->id, $date);
    }

    /**
     * Get exchange rate history for a currency pair
     *
     * @param string $fromCurrencyCode
     * @param string $toCurrencyCode
     * @param int $days Number of days to look back
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRateHistory(string $fromCurrencyCode, string $toCurrencyCode, int $days = 30)
    {
        $fromCurrency = Currency::where('code', $fromCurrencyCode)->first();
        $toCurrency = Currency::where('code', $toCurrencyCode)->first();

        if (!$fromCurrency || !$toCurrency) {
            return collect();
        }

        $startDate = today()->subDays($days);

        return ExchangeRate::where('base_currency_id', $fromCurrency->id)
            ->where('target_currency_id', $toCurrency->id)
            ->where('date', '>=', $startDate)
            ->where('status', 'approved')
            ->orderBy('date', 'desc')
            ->get();
    }
}
