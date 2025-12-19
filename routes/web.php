<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicCustomerQuoteController;
use App\Http\Controllers\PublicProformaInvoiceController;

Route::get('/', function () {
    return view('welcome');
});

// Customer Quote Public Access (Phase 3)
Route::get('/customer-quote/public/{token}', [PublicCustomerQuoteController::class, 'show'])
    ->name('public.customer-quote.show');

Route::post('/customer-quote/public/{token}/select', [PublicCustomerQuoteController::class, 'selectOption'])
    ->name('public.customer-quote.select');

// Proforma Invoice Public Access
Route::get('/proforma-invoice/public/{token}', [PublicProformaInvoiceController::class, 'show'])
    ->name('public.proforma-invoice.show');

Route::get('/proforma-invoice/public/{token}/download', [PublicProformaInvoiceController::class, 'download'])
    ->name('public.proforma-invoice.download');
