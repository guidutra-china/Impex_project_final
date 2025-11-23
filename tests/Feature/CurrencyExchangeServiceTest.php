<?php

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Services\CurrencyExchangeService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    // Clear cache before each test
    Cache::flush();
    
    // Create a base currency
    Currency::factory()->create([
        'code' => 'USD',
        'name' => 'US Dollar',
        'name_plural' => 'US Dollars',
        'symbol' => '$',
        'is_base' => true,
        'is_active' => true,
    ]);

    // Create other currencies
    Currency::factory()->create([
        'code' => 'EUR',
        'name' => 'Euro',
        'name_plural' => 'Euros',
        'symbol' => '€',
        'is_base' => false,
        'is_active' => true,
    ]);

    Currency::factory()->create([
        'code' => 'GBP',
        'name' => 'British Pound',
        'name_plural' => 'British Pounds',
        'symbol' => '£',
        'is_base' => false,
        'is_active' => true,
    ]);

    // Set a fake API key
    config(['services.exchangerate.api_key' => 'test-api-key']);
});

test('it can fetch exchange rates from API', function () {
    // Mock the HTTP response
    Http::fake([
        'v6.exchangerate-api.com/*' => Http::response([
            'result' => 'success',
            'base_code' => 'USD',
            'conversion_rates' => [
                'USD' => 1.0,
                'EUR' => 0.92,
                'GBP' => 0.79,
            ],
        ], 200),
    ]);

    $service = new CurrencyExchangeService();
    $rates = $service->fetchExchangeRates('USD');

    expect($rates)->toBeArray()
        ->and($rates)->toHaveKey('EUR')
        ->and($rates['EUR'])->toBe(0.92);
});

test('it throws exception when API key is not configured', function () {
    config(['services.exchangerate.api_key' => null]);

    $service = new CurrencyExchangeService();
    $service->fetchExchangeRates('USD'); // This will trigger the API key check
})->throws(Exception::class, 'ExchangeRate API key not configured');

test('it throws exception when no base currency is configured', function () {
    Currency::where('is_base', true)->update(['is_base' => false]);

    Http::fake([
        'v6.exchangerate-api.com/*' => Http::response([
            'result' => 'success',
            'base_code' => 'USD',
            'conversion_rates' => ['EUR' => 0.92],
        ], 200),
    ]);

    $service = new CurrencyExchangeService();
    $service->updateAllRates();
})->throws(Exception::class, 'No base currency configured');

test('it can update all currency rates and save to exchange_rates table', function () {
    Http::fake([
        'v6.exchangerate-api.com/*' => Http::response([
            'result' => 'success',
            'base_code' => 'USD',
            'conversion_rates' => [
                'USD' => 1.0,
                'EUR' => 0.92,
                'GBP' => 0.79,
            ],
        ], 200),
    ]);

    $service = new CurrencyExchangeService();
    $stats = $service->updateAllRates();

    expect($stats['updated'])->toBe(2) // EUR and GBP
        ->and($stats['skipped'])->toBe(1) // USD (base currency)
        ->and($stats['failed'])->toBe(0);

    // Verify records were created in exchange_rates table
    $baseCurrency = Currency::where('code', 'USD')->first();
    $eurCurrency = Currency::where('code', 'EUR')->first();
    $gbpCurrency = Currency::where('code', 'GBP')->first();

    $eurRate = ExchangeRate::where('base_currency_id', $baseCurrency->id)
        ->where('target_currency_id', $eurCurrency->id)
        ->where('date', today())
        ->first();

    expect($eurRate)->not->toBeNull()
        ->and($eurRate->rate)->toBe('0.92000000')
        ->and($eurRate->source)->toBe('api')
        ->and($eurRate->source_name)->toBe('ExchangeRate-API')
        ->and($eurRate->status)->toBe('approved');

    $gbpRate = ExchangeRate::where('base_currency_id', $baseCurrency->id)
        ->where('target_currency_id', $gbpCurrency->id)
        ->where('date', today())
        ->first();

    expect($gbpRate)->not->toBeNull()
        ->and($gbpRate->rate)->toBe('0.79000000');
});

test('it skips base currency when updating rates', function () {
    Http::fake([
        'v6.exchangerate-api.com/*' => Http::response([
            'result' => 'success',
            'base_code' => 'USD',
            'conversion_rates' => [
                'USD' => 1.0,
                'EUR' => 0.92,
            ],
        ], 200),
    ]);

    $service = new CurrencyExchangeService();
    $stats = $service->updateAllRates();

    expect($stats['skipped'])->toBe(1);

    // Verify no exchange rate record for base currency
    $baseCurrency = Currency::where('code', 'USD')->first();
    
    $baseRate = ExchangeRate::where('base_currency_id', $baseCurrency->id)
        ->where('target_currency_id', $baseCurrency->id)
        ->first();

    expect($baseRate)->toBeNull();
});

test('it can update a specific currency rate', function () {
    Http::fake([
        'v6.exchangerate-api.com/*' => Http::response([
            'result' => 'success',
            'base_code' => 'USD',
            'conversion_rates' => [
                'EUR' => 0.95,
            ],
        ], 200),
    ]);

    $eurCurrency = Currency::where('code', 'EUR')->first();
    $service = new CurrencyExchangeService();
    $result = $service->updateCurrencyRate($eurCurrency);

    expect($result)->toBeTrue();

    // Verify record in exchange_rates table
    $baseCurrency = Currency::where('code', 'USD')->first();
    $rate = ExchangeRate::where('base_currency_id', $baseCurrency->id)
        ->where('target_currency_id', $eurCurrency->id)
        ->where('date', today())
        ->first();

    expect($rate)->not->toBeNull()
        ->and($rate->rate)->toBe('0.95000000');
});

test('it returns false when trying to update base currency', function () {
    $usdCurrency = Currency::where('code', 'USD')->first();
    $service = new CurrencyExchangeService();
    $result = $service->updateCurrencyRate($usdCurrency);

    expect($result)->toBeFalse();
});

test('it handles API errors gracefully', function () {
    Http::fake([
        'v6.exchangerate-api.com/*' => Http::response([
            'result' => 'error',
            'error-type' => 'invalid-key',
        ], 200),
    ]);

    $service = new CurrencyExchangeService();
    $service->fetchExchangeRates('USD');
})->throws(Exception::class, 'API returned error');

test('it handles HTTP errors gracefully', function () {
    Http::fake([
        'v6.exchangerate-api.com/*' => Http::response([], 500),
    ]);

    $service = new CurrencyExchangeService();
    $service->fetchExchangeRates('USD');
})->throws(Exception::class, 'API request failed');

test('it can convert between currencies using historical rates', function () {
    // Create exchange rate records
    $baseCurrency = Currency::where('code', 'USD')->first();
    $eurCurrency = Currency::where('code', 'EUR')->first();

    $exchangeRate = ExchangeRate::create([
        'base_currency_id' => $baseCurrency->id,
        'target_currency_id' => $eurCurrency->id,
        'rate' => 0.92,
        'inverse_rate' => 1 / 0.92,
        'date' => today()->toDateString(),
        'source' => 'api',
        'source_name' => 'ExchangeRate-API',
        'status' => 'approved',
        'approved_at' => now(),
    ]);

    // Verify the rate was created
    expect($exchangeRate)->not->toBeNull()
        ->and($exchangeRate->base_currency_id)->toBe($baseCurrency->id)
        ->and($exchangeRate->target_currency_id)->toBe($eurCurrency->id);

    $service = new CurrencyExchangeService();
    $result = $service->convert(100, 'USD', 'EUR', today()->toDateString());

    expect($result)->toBe(92.0);
});

test('it tracks failed currency updates', function () {
    Http::fake([
        'v6.exchangerate-api.com/*' => Http::response([
            'result' => 'success',
            'base_code' => 'USD',
            'conversion_rates' => [
                'USD' => 1.0,
                // EUR is missing - should fail
            ],
        ], 200),
    ]);

    $service = new CurrencyExchangeService();
    $stats = $service->updateAllRates();

    expect($stats['failed'])->toBeGreaterThan(0);
});

test('it can get rate history for a currency pair', function () {
    $baseCurrency = Currency::where('code', 'USD')->first();
    $eurCurrency = Currency::where('code', 'EUR')->first();

    // Create historical rates
    ExchangeRate::create([
        'base_currency_id' => $baseCurrency->id,
        'target_currency_id' => $eurCurrency->id,
        'rate' => 0.90,
        'inverse_rate' => 1 / 0.90,
        'date' => today()->subDays(5)->toDateString(),
        'source' => 'api',
        'status' => 'approved',
        'approved_at' => now(),
    ]);

    ExchangeRate::create([
        'base_currency_id' => $baseCurrency->id,
        'target_currency_id' => $eurCurrency->id,
        'rate' => 0.92,
        'inverse_rate' => 1 / 0.92,
        'date' => today()->toDateString(),
        'source' => 'api',
        'source_name' => 'ExchangeRate-API',
        'status' => 'approved',
        'approved_at' => now(),
    ]);

    $service = new CurrencyExchangeService();
    $history = $service->getRateHistory('USD', 'EUR', 30);

    expect($history)->toHaveCount(2)
        ->and($history->first()->rate)->toBe('0.92000000')
        ->and($history->last()->rate)->toBe('0.90000000');
});
test('it can get latest rate for a currency pair', function () {
    // Create exchange rate
    $baseCurrency = Currency::where('code', 'USD')->first();
    $eurCurrency = Currency::where('code', 'EUR')->first();

    ExchangeRate::create([
        'base_currency_id' => $baseCurrency->id,
        'target_currency_id' => $eurCurrency->id,
        'rate' => 0.92,
        'inverse_rate' => 1 / 0.92,
        'date' => today()->toDateString(),
        'source' => 'api',
        'source_name' => 'ExchangeRate-API',
        'status' => 'approved',
        'approved_at' => now(),
    ]);

    $service = new CurrencyExchangeService();
    $rate = $service->getLatestRate('USD', 'EUR');

    expect($rate)->not->toBeNull()
        ->and($rate->rate)->toBe('0.92000000');
});
