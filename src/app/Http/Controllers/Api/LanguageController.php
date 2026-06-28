<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{
    use ApiResponse;

    public function switch(Request $request)
    {
        $request->validate([
            'locale' => 'required|in:fa,en,ar',
        ]);

        $locale = $request->locale;
        App::setLocale($locale);
        Session::put('locale', $locale);

        return $this->success([
            'locale' => $locale,
            'message' => trans('messages.language_switched'),
        ], 'Language switched successfully');
    }

    public function current()
    {
        return $this->success([
            'locale' => App::getLocale(),
            'locales' => [
                'fa' => ['name' => 'فارسی', 'flag' => '🇮🇷', 'dir' => 'rtl'],
                'en' => ['name' => 'English', 'flag' => '🇬🇧', 'dir' => 'ltr'],
                'ar' => ['name' => 'العربية', 'flag' => '🇸🇦', 'dir' => 'rtl'],
            ],
        ]);
    }

    public function translations(Request $request)
    {
        $locale = App::getLocale();
        $translations = $this->loadTranslations($locale);

        return $this->success($translations);
    }

    private function loadTranslations(string $locale): array
    {
        $files = glob(resource_path("lang/{$locale}/*.php"));
        $translations = [];

        foreach ($files as $file) {
            $key = pathinfo($file, PATHINFO_FILENAME);
            $translations[$key] = include $file;
        }

        return $translations;
    }
}
