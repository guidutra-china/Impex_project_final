<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== LOCALE DEBUG ===\n\n";

// 1. Check config
echo "1. CONFIG:\n";
echo "   app.locale: " . config('app.locale') . "\n";
echo "   app.fallback_locale: " . config('app.fallback_locale') . "\n";
echo "   app.available_locales: " . json_encode(config('app.available_locales')) . "\n\n";

// 2. Check current locale
echo "2. CURRENT LOCALE:\n";
echo "   app()->getLocale(): " . app()->getLocale() . "\n\n";

// 3. Check if translation files exist
echo "3. TRANSLATION FILES:\n";
$locales = ['en', 'zh_CN'];
foreach ($locales as $locale) {
    $path = base_path("lang/$locale/navigation.php");
    echo "   $locale: " . (file_exists($path) ? "EXISTS" : "MISSING") . "\n";
}
echo "\n";

// 4. Test translations
echo "4. TEST TRANSLATIONS:\n";
foreach ($locales as $locale) {
    app()->setLocale($locale);
    echo "   Locale: $locale\n";
    echo "   navigation.shipments: " . __('navigation.shipments') . "\n";
    echo "   navigation.logistics_shipping: " . __('navigation.logistics_shipping') . "\n";
}
echo "\n";

// 5. Check middleware
echo "5. MIDDLEWARE:\n";
$middlewarePath = app_path('Http/Middleware/SetLocale.php');
echo "   SetLocale.php: " . (file_exists($middlewarePath) ? "EXISTS" : "MISSING") . "\n";

// 6. Check bootstrap/app.php
echo "\n6. BOOTSTRAP CONFIG:\n";
$bootstrapContent = file_get_contents(base_path('bootstrap/app.php'));
echo "   Has SetLocale middleware: " . (strpos($bootstrapContent, 'SetLocale') !== false ? "YES" : "NO") . "\n";

echo "\n=== END DEBUG ===\n";
