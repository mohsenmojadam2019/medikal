<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use App\Models\Language;
use App\Services\LanguageService;

class SetLocale
{
    protected LanguageService $languageService;

    public function __construct(LanguageService $languageService)
    {
        $this->languageService = $languageService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $locale = $this->determineLocale($request);
        
        // تنظیم زبان اپلیکیشن
        App::setLocale($locale);

        // تنظیم جهت (rtl/ltr)
        $language = $this->languageService->getLanguageByCode($locale);
        if ($language) {
            config(['app.direction' => $language->direction]);
        }

        return $next($request);
    }

    /**
     * تشخیص زبان از منابع مختلف
     */
    protected function determineLocale(Request $request): string
    {
        // اولویت 1: Header X-Locale
        if ($request->hasHeader('X-Locale')) {
            $locale = $request->header('X-Locale');
            if ($this->isValidLocale($locale)) {
                return $locale;
            }
        }

        // اولویت 2: Accept-Language Header
        if ($request->hasHeader('Accept-Language')) {
            $locale = $request->header('Accept-Language');
            if ($this->isValidLocale($locale)) {
                return $locale;
            }
        }

        // اولویت 3: Session
        if (session()->has('locale')) {
            $locale = session('locale');
            if ($this->isValidLocale($locale)) {
                return $locale;
            }
        }

        // اولویت 4: کاربر لاگین شده
        if (auth()->check() && auth()->user()->language) {
            $locale = auth()->user()->language;
            if ($this->isValidLocale($locale)) {
                return $locale;
            }
        }

        // اولویت 5: Cookie
        if ($request->hasCookie('locale')) {
            $locale = $request->cookie('locale');
            if ($this->isValidLocale($locale)) {
                return $locale;
            }
        }

        // اولویت 6: زبان پیش‌فرض سیستم
        return config('app.locale', 'fa');
    }

    /**
     * اعتبارسنجی کد زبان
     */
    protected function isValidLocale(string $locale): bool
    {
        $allowed = Cache::remember('languages.codes', 3600, function () {
            return Language::where('is_active', true)->pluck('code')->toArray();
        });

        return in_array($locale, $allowed);
    }
}
