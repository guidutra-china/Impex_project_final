<?php

use App\Models\Currency;
use App\Services\CurrencyExchangeService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    // Create a base currency
    Currency::factory()->create([
        'code' => 'USD',
        'name' => 'US Dollar',
        'name_plural' => 'US Dollars',
        'symbol' => '$',
        'exchange_rate' => 1.0000,
        'is_base' => true,
        'is_active' => true,
    ]);

    // Create other currencies
    Currency::factory()->create([
        'code' => 'EUR',
        'name' => 'Euro',
        'name_plural' => 'Euros',
        'symbol' => '€',
        'exchange_rate' => 0.85,
        'is_base' => false,
        'is_active' => true,
    ]);

    Currency::factory()->create([
        'code' => 'GBP',
        'name' => 'British Pound',
        'name_plural' => 'British Pounds',
        'symbol' => '£',
        'exchange_rate' => 0.73,
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
                'BRL' => 5.45,
            ],
        ], 200),
    ]);

    $service = new CurrencyExchangeService();
    $rates = $service->fetchExchangeRates('USD');

    expect($rates)->toBeArray();
    expect($rates)->toHaveKey('EUR');
    expect($rates['EUR'])->toBe(0.92);
});

test('it throws exception when API key is not configured', function () {
    config(['services.exchangerate.api_key' => null]);

    $service = new CurrencyExchangeService();
    
    expect(fn() => $service->fetchExchangeRates('USD'))
        ->toThrow(Exception::class, 'ExchangeRate API key not configured');
});

test('it throws exception when no base currency is configured', function () {
    Currency::where('is_base', true)->update(['is_base' => false]);

    $service = new CurrencyExchangeService();
    
    expect(fn() => $service->updateAllRates())
        ->toThrow(Exception::class, 'No base currency configured');
});

test('it can update all currency rates', function () {
    // Mock the HTTP response
    Http::fake([
        'v6.exchangerate-api.com/*' => Http::response([
            'result' => 'success',
            'base_code' => 'USD',
            'conversion_rates' => [
                'USD' => 1.0,
                'EUR' => 0.92,
                'GBP' => 0.79,
                'BRL' => 5.45,
            ],
        ], 200),
    ]);

    $service = new CurrencyExchangeService();
    $stats = $service->updateAllRates();

    expect($stats)->toBeArray();
    expect($stats)->toHaveKey('updated');
    expect($stats)->toHaveKey('failed');
    expect($stats)->toHaveKey('skipped');
    expect($stats['updated'])->toBe(2); // EUR and GBP
    expect($stats['skipped'])->toBe(1); // USD (base currency)

    // Verify rates were updated
    $eur = Currency::where('code', 'EUR')->first();
    expect($eur->exchange_rate)->toBe(0.92);

    $gbp = Currency::where('code', 'GBP')->first();
    expect($gbp->exchange_rate)->toBe(0.79);
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

    $usd = Currency::where('code', 'USD')->first();
    expect($usd->exchange_rate)->toBe(1.0000); // Should remain 1.0
    expect($stats['skipped'])->toBe(1);
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

    $service = new CurrencyExchangeService();
    $eur = Currency::where('code', 'EUR')->first();
    
    $result = $service->updateCurrencyRate($eur);

    expect($result)->toBeTrue();
    
    $eur->refresh();
    expect($eur->exchange_rate)->toBe(0.95);
});

test('it returns false when trying to update base currency', function () {
    $service = new CurrencyExchangeService();
    $usd = Currency::where('code', 'USD')->first();
    
    $result = $service->updateCurrencyRate($usd);

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
    
    expect(fn() => $service->fetchExchangeRates('USD'))
        ->toThrow(Exception::class, 'API returned error: invalid-key');
});

test('it handles HTTP errors gracefully', function () {
    Http::fake([
        'v6.exchangerate-api.com/*' => Http::response([], 500),
    ]);

    $service = new CurrencyExchangeService();
    
    expect(fn() => $service->fetchExchangeRates('USD'))
        ->toThrow(Exception::class);
});

test('it can convert between currencies', function () {
    Http::fake([
        'v6.exchangerate-api.com/*' => Http::response([
            'result' => 'success',
            'base_code' => 'USD',
            'conversion_rates' => [
                'EUR' => 0.92,
            ],
        ], 200),
    ]);

    $service = new CurrencyExchangeService();
    $converted = $service->convert(100, 'USD', 'EUR');

    expect($converted)->toBe(92.0);
});

test('it tracks failed currency updates', function () {
    Http::fake([
        'v6.exchangerate-api.com/*' => Http::response([
            'result' => 'success',
            'base_code' => 'USD',
            'conversion_rates' => [
                'EUR' => 0.92,
                // GBP is missing - should fail
            ],
        ], 200),
    ]);

    $service = new CurrencyExchangeService();
    $stats = $service->updateAllRates();

    expect($stats['updated'])->toBe(1); // Only EUR
    expect($stats['failed'])->toBe(1); // GBP failed
});
