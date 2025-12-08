<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

echo "Available locales: " . json_encode(config('app.available_locales')) . "\n";
echo "Current locale: " . app()->getLocale() . "\n";
echo "Fallback locale: " . config('app.fallback_locale') . "\n";

// Test translation
echo "\nTest translation:\n";
echo "fields.name (EN): " . __('fields.name') . "\n";

// Switch to Chinese
app()->setLocale('zh_CN');
echo "\nAfter switching to zh_CN:\n";
echo "Current locale: " . app()->getLocale() . "\n";
echo "fields.name (ZH): " . __('fields.name') . "\n";
