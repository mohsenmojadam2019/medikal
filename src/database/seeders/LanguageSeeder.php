<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Language;
use App\Models\Translation;
use App\Models\TranslationFallback;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // پاک کردن داده‌های قبلی (اختیاری)
        // DB::table('translations')->truncate();
        // DB::table('translation_fallbacks')->truncate();
        // DB::table('languages')->truncate();

        // ==========================================
        // 1. ایجاد زبان‌های اصلی
        // ==========================================
        $languages = [
            [
                'code' => 'fa',
                'name' => 'فارسی',
                'native_name' => 'فارسی',
                'direction' => 'rtl',
                'is_active' => true,
                'is_default' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ar',
                'name' => 'العربية',
                'native_name' => 'العربية',
                'direction' => 'rtl',
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'en',
                'name' => 'English',
                'native_name' => 'English',
                'direction' => 'ltr',
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'tr',
                'name' => 'Türkçe',
                'native_name' => 'Türkçe',
                'direction' => 'ltr',
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ur',
                'name' => 'اردو',
                'native_name' => 'اردو',
                'direction' => 'rtl',
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ku',
                'name' => 'کوردی',
                'native_name' => 'کوردی',
                'direction' => 'rtl',
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'ps',
                'name' => 'پښتو',
                'native_name' => 'پښتو',
                'direction' => 'rtl',
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($languages as $language) {
            Language::updateOrCreate(
                ['code' => $language['code']],
                $language
            );
        }

        // ==========================================
        // 2. تنظیم Fallback برای زبان‌ها
        // ==========================================
        $fa = Language::where('code', 'fa')->first();
        $ar = Language::where('code', 'ar')->first();
        $en = Language::where('code', 'en')->first();
        $tr = Language::where('code', 'tr')->first();
        $ur = Language::where('code', 'ur')->first();
        $ku = Language::where('code', 'ku')->first();
        $ps = Language::where('code', 'ps')->first();

        // تنظیم fallback: اگر ترجمه‌ای وجود نداشت، به فارسی برگردد
        $fallbacks = [
            ['language_id' => $ar->id, 'fallback_language_id' => $fa->id],
            ['language_id' => $en->id, 'fallback_language_id' => $fa->id],
            ['language_id' => $tr->id, 'fallback_language_id' => $fa->id],
            ['language_id' => $ur->id, 'fallback_language_id' => $fa->id],
            ['language_id' => $ku->id, 'fallback_language_id' => $fa->id],
            ['language_id' => $ps->id, 'fallback_language_id' => $fa->id],
        ];

        foreach ($fallbacks as $fallback) {
            TranslationFallback::updateOrCreate(
                ['language_id' => $fallback['language_id']],
                $fallback
            );
        }

        // ==========================================
        // 3. ایجاد ترجمه‌های پایه (Essential)
        // ==========================================
        $this->createEssentialTranslations();

        // ==========================================
        // 4. ایمپورت از فایل‌های lang اگر وجود داشته باشند
        // ==========================================
        $this->importFromFilesIfExist();
    }

    /**
     * ایجاد ترجمه‌های ضروری برای همه زبان‌ها
     */
    private function createEssentialTranslations(): void
    {
        $languages = Language::whereIn('code', ['fa', 'ar', 'en'])->get();
        
        $translations = [
            // Auth
            ['group' => 'auth', 'key' => 'failed', 'values' => [
                'fa' => 'اطلاعات وارد شده صحیح نیست.',
                'ar' => 'المعلومات المدخلة غير صحيحة.',
                'en' => 'These credentials do not match our records.',
            ]],
            ['group' => 'auth', 'key' => 'password', 'values' => [
                'fa' => 'رمز عبور اشتباه است.',
                'ar' => 'كلمة المرور غير صحيحة.',
                'en' => 'The provided password is incorrect.',
            ]],
            ['group' => 'auth', 'key' => 'throttle', 'values' => [
                'fa' => 'تعداد تلاش‌های ناموفق بیش از حد مجاز است. لطفاً پس از :seconds ثانیه دوباره تلاش کنید.',
                'ar' => 'محاولات كثيرة جداً. يرجى المحاولة بعد :seconds ثانية.',
                'en' => 'Too many login attempts. Please try again in :seconds seconds.',
            ]],

            // Messages
            ['group' => 'messages', 'key' => 'welcome', 'values' => [
                'fa' => 'به سیستم مدیریت کلینیک خوش آمدید',
                'ar' => 'مرحباً بك في نظام إدارة العيادة',
                'en' => 'Welcome to Clinic Management System',
            ]],
            ['group' => 'messages', 'key' => 'dashboard', 'values' => [
                'fa' => 'داشبورد',
                'ar' => 'لوحة القيادة',
                'en' => 'Dashboard',
            ]],
            ['group' => 'messages', 'key' => 'doctors', 'values' => [
                'fa' => 'پزشکان',
                'ar' => 'الأطباء',
                'en' => 'Doctors',
            ]],
            ['group' => 'messages', 'key' => 'patients', 'values' => [
                'fa' => 'بیماران',
                'ar' => 'المرضى',
                'en' => 'Patients',
            ]],
            ['group' => 'messages', 'key' => 'appointments', 'values' => [
                'fa' => 'نوبت‌ها',
                'ar' => 'المواعيد',
                'en' => 'Appointments',
            ]],
            ['group' => 'messages', 'key' => 'prescriptions', 'values' => [
                'fa' => 'نسخه‌ها',
                'ar' => 'الوصفات الطبية',
                'en' => 'Prescriptions',
            ]],
            ['group' => 'messages', 'key' => 'drugs', 'values' => [
                'fa' => 'داروها',
                'ar' => 'الأدوية',
                'en' => 'Drugs',
            ]],
            ['group' => 'messages', 'key' => 'invoices', 'values' => [
                'fa' => 'فاکتورها',
                'ar' => 'الفواتير',
                'en' => 'Invoices',
            ]],
            ['group' => 'messages', 'key' => 'settings', 'values' => [
                'fa' => 'تنظیمات',
                'ar' => 'الإعدادات',
                'en' => 'Settings',
            ]],
            ['group' => 'messages', 'key' => 'search', 'values' => [
                'fa' => 'جستجو...',
                'ar' => 'بحث...',
                'en' => 'Search...',
            ]],
            ['group' => 'messages', 'key' => 'save', 'values' => [
                'fa' => 'ذخیره',
                'ar' => 'حفظ',
                'en' => 'Save',
            ]],
            ['group' => 'messages', 'key' => 'cancel', 'values' => [
                'fa' => 'انصراف',
                'ar' => 'إلغاء',
                'en' => 'Cancel',
            ]],
            ['group' => 'messages', 'key' => 'delete', 'values' => [
                'fa' => 'حذف',
                'ar' => 'حذف',
                'en' => 'Delete',
            ]],
            ['group' => 'messages', 'key' => 'edit', 'values' => [
                'fa' => 'ویرایش',
                'ar' => 'تعديل',
                'en' => 'Edit',
            ]],
            ['group' => 'messages', 'key' => 'view', 'values' => [
                'fa' => 'مشاهده',
                'ar' => 'عرض',
                'en' => 'View',
            ]],
            ['group' => 'messages', 'key' => 'create', 'values' => [
                'fa' => 'ایجاد جدید',
                'ar' => 'إنشاء جديد',
                'en' => 'Create New',
            ]],
            ['group' => 'messages', 'key' => 'loading', 'values' => [
                'fa' => 'در حال بارگذاری...',
                'ar' => 'جاري التحميل...',
                'en' => 'Loading...',
            ]],
            ['group' => 'messages', 'key' => 'no_data', 'values' => [
                'fa' => 'هیچ داده‌ای یافت نشد',
                'ar' => 'لم يتم العثور على بيانات',
                'en' => 'No data found',
            ]],
            ['group' => 'messages', 'key' => 'error', 'values' => [
                'fa' => 'خطا!',
                'ar' => 'خطأ!',
                'en' => 'Error!',
            ]],
            ['group' => 'messages', 'key' => 'success', 'values' => [
                'fa' => 'موفق!',
                'ar' => 'نجاح!',
                'en' => 'Success!',
            ]],
            ['group' => 'messages', 'key' => 'confirm_delete', 'values' => [
                'fa' => 'آیا از حذف این آیتم اطمینان دارید؟',
                'ar' => 'هل أنت متأكد من حذف هذا العنصر؟',
                'en' => 'Are you sure you want to delete this item?',
            ]],
        ];

        foreach ($translations as $translation) {
            foreach ($languages as $language) {
                $value = $translation['values'][$language->code] ?? $translation['values']['fa'];
                
                Translation::updateOrCreate(
                    [
                        'language_id' => $language->id,
                        'group_name' => $translation['group'],
                        'key_name' => $translation['key'],
                    ],
                    [
                        'value' => $value,
                        'is_plural' => false,
                    ]
                );
            }
        }
    }

    /**
     * ایمپورت از فایل‌های lang اگر وجود داشته باشند
     */
    private function importFromFilesIfExist(): void
    {
        $locales = ['fa', 'ar', 'en'];
        
        foreach ($locales as $locale) {
            $langPath = resource_path("lang/{$locale}");
            if (!is_dir($langPath)) {
                continue;
            }

            $language = Language::where('code', $locale)->first();
            if (!$language) {
                continue;
            }

            foreach (glob("{$langPath}/*.php") as $file) {
                $group = pathinfo($file, PATHINFO_FILENAME);
                $translations = require $file;

                foreach ($translations as $key => $value) {
                    if (is_array($value)) {
                        // ترجمه‌های جمع (plural)
                        foreach ($value as $rule => $text) {
                            Translation::updateOrCreate(
                                [
                                    'language_id' => $language->id,
                                    'group_name' => $group,
                                    'key_name' => $key,
                                    'plural_rule' => $rule,
                                ],
                                [
                                    'value' => $text,
                                    'is_plural' => true,
                                ]
                            );
                        }
                    } else {
                        Translation::updateOrCreate(
                            [
                                'language_id' => $language->id,
                                'group_name' => $group,
                                'key_name' => $key,
                            ],
                            [
                                'value' => $value,
                                'is_plural' => false,
                            ]
                        );
                    }
                }
            }
        }
    }
}
