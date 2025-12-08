<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\App;

class LocaleSwitcher extends Widget
{
    protected static string $view = 'filament.widgets.locale-switcher';
    
    protected int | string | array $columnSpan = 'full';
    
    public function getAvailableLocales(): array
    {
        return config('app.available_locales', ['en' => 'English']);
    }
    
    public function getCurrentLocale(): string
    {
        return App::getLocale();
    }
    
    public function switchLocale(string $locale): void
    {
        if (array_key_exists($locale, $this->getAvailableLocales())) {
            // Update user preference
            if (auth()->check()) {
                auth()->user()->update(['locale' => $locale]);
            }
            
            // Update session
            session(['locale' => $locale]);
            
            // Redirect to refresh page with new locale
            redirect(request()->header('Referer'));
        }
    }
}
