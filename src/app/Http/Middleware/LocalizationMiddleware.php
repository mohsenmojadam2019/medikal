<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LocalizationMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Get language from header, session, or default
        $locale = $request->header('Accept-Language');

        if (!$locale) {
            $locale = Session::get('locale', config('app.locale', 'fa'));
        }

        // Validate locale
        $allowedLocales = ['fa', 'en', 'ar'];
        if (!in_array($locale, $allowedLocales)) {
            $locale = 'fa';
        }

        App::setLocale($locale);
        Session::put('locale', $locale);

        return $next($request);
    }
}
