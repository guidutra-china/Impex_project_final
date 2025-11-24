<?php

use App\Models\CompanySetting;

if (!function_exists('companySettings')) {
    /**
     * Get the current company settings
     *
     * @return CompanySetting|null
     */
    function companySettings(): ?CompanySetting
    {
        return CompanySetting::current();
    }
}

if (!function_exists('companyName')) {
    /**
     * Get the company name
     *
     * @return string
     */
    function companyName(): string
    {
        $settings = companySettings();
        return $settings?->company_name ?? 'Your Company Name';
    }
}

if (!function_exists('companyLogo')) {
    /**
     * Get the company logo path for PDFs
     *
     * @return string|null
     */
    function companyLogo(): ?string
    {
        $settings = companySettings();
        return $settings?->logo_full_path;
    }
}

if (!function_exists('companyAddress')) {
    /**
     * Get the formatted company address
     *
     * @return string
     */
    function companyAddress(): string
    {
        $settings = companySettings();
        return $settings?->full_address ?? '';
    }
}

if (!function_exists('money')) {
    /**
     * Format an amount as money with currency symbol
     *
     * @param int $cents Amount in cents
     * @param string $currencyCode Currency code (e.g., 'USD', 'BRL')
     * @return string Formatted money string
     */
    function money(int $cents, string $currencyCode): string
    {
        $amount = $cents / 100;
        
        // Currency symbols mapping
        $symbols = [
            'USD' => '$',
            'BRL' => 'R$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'CNY' => '¥',
        ];
        
        $symbol = $symbols[$currencyCode] ?? $currencyCode . ' ';
        
        // Format with 2 decimals and thousands separator
        return $symbol . number_format($amount, 2, '.', ',');
    }
}
