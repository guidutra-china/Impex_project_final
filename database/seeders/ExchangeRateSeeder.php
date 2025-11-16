<?php
use App\Models\Currency;
use App\Models\ExchangeRate;

$usd = Currency::where('code', 'USD')->first();
$eur = Currency::where('code', 'EUR')->first();
$gbp = Currency::where('code', 'GBP')->first();

// USD → EUR
ExchangeRate::create([
    'base_currency_id' => $usd->id,
    'target_currency_id' => $eur->id,
    'rate' => 0.85,
    'date' => today(),
    'source' => 'manual',
    'status' => 'approved',
]);

// USD → GBP
ExchangeRate::create([
    'base_currency_id' => $usd->id,
    'target_currency_id' => $gbp->id,
    'rate' => 0.75,
    'date' => today(),
    'source' => 'manual',
    'status' => 'approved',
]);