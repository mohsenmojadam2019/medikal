<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Services\Language\LanguageService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;

class LanguageController extends Controller
{
    use ApiResponse;

    protected LanguageService $languageService;

    public function __construct(LanguageService $languageService)
    {
        $this->languageService = $languageService;
    }

    /**
     * دریافت لیست زبان‌های فعال
     */
    public function index()
    {
        $languages = $this->languageService->getActiveLanguages();

        return $this->success([
            'languages' => $languages,
            'current' => app()->getLocale(),
            'default' => config('app.locale'),
            'direction' => config('app.direction', 'ltr'),
        ]);
    }

    /**
     * دریافت زبان فعلی
     */
    public function current()
    {
        return $this->success([
            'locale' => app()->getLocale(),
            'direction' => config('app.direction', 'ltr'),
            'language' => $this->languageService->getLanguageByCode(app()->getLocale()),
        ]);
    }

    /**
     * تغییر زبان
     */
    public function switch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'locale' => 'required|string|exists:languages,code',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        $locale = $request->input('locale');
        $language = $this->languageService->getLanguageByCode($locale);

        if (!$language || !$language->is_active) {
            return $this->error('زبان مورد نظر فعال نیست', 400);
        }

        // ذخیره در session
        session()->put('locale', $locale);

        // ذخیره در cookie (30 روز)
        cookie()->queue('locale', $locale, 60 * 24 * 30);

        // اگر کاربر لاگین است، در دیتابیس ذخیره کن
        if (auth()->check()) {
            auth()->user()->update(['language' => $locale]);
        }

        // تنظیم زبان اپلیکیشن
        App::setLocale($locale);

        return $this->success([
            'locale' => $locale,
            'direction' => $language->direction,
            'translations' => $this->languageService->getFrontendTranslations($locale),
        ], 'زبان با موفقیت تغییر کرد');
    }

    /**
     * دریافت تمام ترجمه‌ها برای فرانت‌اند
     */
    public function translations(Request $request)
    {
        $locale = $request->input('locale', app()->getLocale());

        $translations = $this->languageService->getFrontendTranslations($locale);

        return $this->success([
            'locale' => $locale,
            'translations' => $translations,
        ]);
    }

    /**
     * دریافت ترجمه یک کلید خاص
     */
    public function getTranslation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string',
            'locale' => 'nullable|string|exists:languages,code',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        $locale = $request->input('locale', app()->getLocale());
        $value = $this->languageService->getTranslation($request->input('key'), $locale);

        return $this->success([
            'key' => $request->input('key'),
            'locale' => $locale,
            'value' => $value,
        ]);
    }

    /**
     * تنظیم یا به‌روزرسانی یک ترجمه (فقط ادمین)
     */
    public function setTranslation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'group' => 'required|string',
            'key' => 'required|string',
            'value' => 'required|string',
            'locale' => 'nullable|string|exists:languages,code',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        $translation = $this->languageService->setTranslation(
            $request->input('group'),
            $request->input('key'),
            $request->input('value'),
            $request->input('locale', app()->getLocale())
        );

        return $this->success($translation, 'ترجمه با موفقیت ذخیره شد');
    }

    /**
     * ایمپورت ترجمه‌ها از فایل‌های PHP (فقط ادمین)
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'locale' => 'required|string|exists:languages,code',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        $count = $this->languageService->importFromFiles($request->input('locale'));

        return $this->success([
            'count' => $count,
            'locale' => $request->input('locale'),
        ], "{$count} ترجمه با موفقیت ایمپورت شد");
    }

    /**
     * خروجی گرفتن از ترجمه‌ها به فایل (فقط ادمین)
     */
    public function export(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'locale' => 'required|string|exists:languages,code',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        $count = $this->languageService->exportToFiles($request->input('locale'));

        return $this->success([
            'count' => $count,
            'locale' => $request->input('locale'),
        ], "{$count} ترجمه با موفقیت خروجی گرفته شد");
    }

    /**
     * مدیریت زبان‌ها (فقط ادمین)
     */
    public function manage(Request $request)
    {
        $languages = Language::orderBy('sort_order')->get();

        return $this->success($languages);
    }

    /**
     * ایجاد زبان جدید (فقط ادمین)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|unique:languages,code|max:10',
            'name' => 'required|string|max:100',
            'native_name' => 'required|string|max:100',
            'direction' => 'required|in:rtl,ltr',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        $language = $this->languageService->createLanguage($request->all());

        return $this->success($language, 'زبان با موفقیت ایجاد شد', 201);
    }

    /**
     * به‌روزرسانی زبان (فقط ادمین)
     */
    public function update(Request $request, $id)
    {
        $language = Language::find($id);
        if (!$language) {
            return $this->error('زبان یافت نشد', 404);
        }

        $validator = Validator::make($request->all(), [
            'code' => 'sometimes|string|unique:languages,code,' . $id . '|max:10',
            'name' => 'sometimes|string|max:100',
            'native_name' => 'sometimes|string|max:100',
            'direction' => 'sometimes|in:rtl,ltr',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        // اگر می‌خواهد این زبان پیش‌فرض شود
        if ($request->has('is_default') && $request->input('is_default')) {
            $this->languageService->setDefaultLanguage($language);
        }

        $language = $this->languageService->updateLanguage($language, $request->all());

        return $this->success($language, 'زبان با موفقیت به‌روزرسانی شد');
    }

    /**
     * حذف زبان (فقط ادمین)
     */
    public function destroy($id)
    {
        $language = Language::find($id);
        if (!$language) {
            return $this->error('زبان یافت نشد', 404);
        }

        if ($language->is_default) {
            return $this->error('نمی‌توان زبان پیش‌فرض را حذف کرد', 400);
        }

        $this->languageService->deleteLanguage($language);

        return $this->success(null, 'زبان با موفقیت حذف شد');
    }

    /**
     * تغییر وضعیت زبان (فقط ادمین)
     */
    public function toggle($id)
    {
        $language = Language::find($id);
        if (!$language) {
            return $this->error('زبان یافت نشد', 404);
        }

        $language = $this->languageService->toggleLanguage($language);

        return $this->success($language, 'وضعیت زبان با موفقیت تغییر کرد');
    }

    /**
     * تنظیم fallback برای زبان (فقط ادمین)
     */
    public function setFallback(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'language_id' => 'required|exists:languages,id',
            'fallback_id' => 'required|exists:languages,id|different:language_id',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        $language = Language::find($request->input('language_id'));
        $fallback = Language::find($request->input('fallback_id'));

        $this->languageService->setFallback($language, $fallback);

        return $this->success(null, 'Fallback با موفقیت تنظیم شد');
    }
}
