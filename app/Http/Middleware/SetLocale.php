<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Priority:
        // 1. User preference (from database)
        // 2. Session
        // 3. Browser language
        // 4. Default locale
        
        $locale = null;
        
        // 1. User preference
        if (auth()->check() && auth()->user()->locale) {
            $locale = auth()->user()->locale;
        }
        
        // 2. Session
        if (!$locale && Session::has('locale')) {
            $locale = Session::get('locale');
        }
        
        // 3. Browser language
        if (!$locale) {
            $availableLocales = array_keys(config('app.available_locales', ['en']));
            $locale = $request->getPreferredLanguage($availableLocales);
        }
        
        // 4. Default
        if (!$locale) {
            $locale = config('app.locale', 'en');
        }
        
        // Validate locale
        if (!in_array($locale, array_keys(config('app.available_locales', ['en'])))) {
            $locale = config('app.locale', 'en');
        }
        
        App::setLocale($locale);
        Session::put('locale', $locale);
        
        return $next($request);
    }
}
