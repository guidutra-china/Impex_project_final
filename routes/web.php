<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Customer Quote Public Access (Phase 3 - Placeholder)
Route::get('/customer-quote/public/{token}', function ($token) {
    return view('customer-quote-public', ['token' => $token]);
})->name('customer-quote.public');
