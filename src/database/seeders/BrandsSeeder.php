<?php

// database/seeders/BrandsSeeder.php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandsSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = \App\Models\Tenant::first()?->id ?? 1;

        $brands = [
            // ============================================================
            // ✅ برندهای ایرانی داروسازی
            // ============================================================
            [
                'name' => 'داروسازی لقمان',
                'slug' => 'loghman',
                'website' => 'https://loghmanpharma.com',
                'description' => 'یکی از قدیمی‌ترین و معتبرترین شرکت‌های داروسازی ایران',
            ],
            [
                'name' => 'داروسازی البرز',
                'slug' => 'alborz',
                'website' => 'https://alborzdaru.com',
                'description' => 'تولیدکننده انواع داروهای ژنریک و اختصاصی',
            ],
            [
                'name' => 'داروسازی سیناژن',
                'slug' => 'sinagen',
                'website' => 'https://sinagen.com',
                'description' => 'تولیدکننده داروهای بیوتکنولوژی و هورمونی',
            ],
            [
                'name' => 'داروسازی فارابی',
                'slug' => 'farabi',
                'website' => 'https://farabipharma.com',
                'description' => 'تولیدکننده داروهای دیابت و قلب و عروق',
            ],
            [
                'name' => 'داروسازی حکیم',
                'slug' => 'hakim',
                'website' => 'https://hakimpharma.com',
                'description' => 'تولیدکننده داروهای ضدالتهاب و مسکن',
            ],
            [
                'name' => 'داروسازی ایران',
                'slug' => 'iran-pharma',
                'website' => 'https://iranpharma.com',
                'description' => 'قدیمی‌ترین شرکت داروسازی ایران',
            ],
            [
                'name' => 'داروسازی رازک',
                'slug' => 'razak',
                'website' => 'https://razakpharma.com',
                'description' => 'تولیدکننده داروهای گوارشی و معده',
            ],
            [
                'name' => 'داروسازی ابوریحان',
                'slug' => 'aboureihan',
                'website' => 'https://aboureihan.com',
                'description' => 'تولیدکننده آنتی‌بیوتیک‌ها و داروهای ضدباکتری',
            ],
            [
                'name' => 'داروسازی شهید قاضی',
                'slug' => 'shahid-ghazi',
                'website' => 'https://shahidghazi.com',
                'description' => 'تولیدکننده داروهای اعصاب و روان',
            ],
            [
                'name' => 'داروسازی ویتانا',
                'slug' => 'vitana',
                'website' => 'https://vitana.com',
                'description' => 'تولیدکننده مکمل‌های غذایی و ویتامین‌ها',
            ],
            [
                'name' => 'داروسازی پورسینا',
                'slug' => 'poursina',
                'website' => 'https://poursina.com',
                'description' => 'تولیدکننده داروهای آنتی‌هیستامین و تنفسی',
            ],
            [
                'name' => 'داروسازی آریا',
                'slug' => 'arya',
                'website' => 'https://aryapharma.com',
                'description' => 'تولیدکننده داروهای تنفسی و اسپری‌ها',
            ],
            [
                'name' => 'داروسازی حیات',
                'slug' => 'hayat',
                'website' => 'https://hayatpharma.com',
                'description' => 'تولیدکننده داروهای گوارشی و ملین‌ها',
            ],
            [
                'name' => 'داروسازی آریان کیمیا تک',
                'slug' => 'arian-kimiya-tec',
                'website' => 'https://ariankimiyatec.com',
                'description' => 'تولیدکننده محصولات آرایشی و بهداشتی تحت لیسانس آلمان',
            ],

            // ============================================================
            // ✅ برندهای خارجی معروف
            // ============================================================
            [
                'name' => 'نوو نوردیسک',
                'slug' => 'novo-nordisk',
                'website' => 'https://novonordisk.com',
                'description' => 'شرکت دانمارکی تولیدکننده داروهای دیابت',
            ],
            [
                'name' => 'سانوفی',
                'slug' => 'sanofi',
                'website' => 'https://sanofi.com',
                'description' => 'شرکت فرانسوی تولیدکننده داروهای مختلف',
            ],
            [
                'name' => 'گلاکسواسمیت‌کلاین',
                'slug' => 'gsk',
                'website' => 'https://gsk.com',
                'description' => 'شرکت بریتانیایی تولیدکننده داروهای تنفسی و واکسن',
            ],
            [
                'name' => 'فایزر',
                'slug' => 'pfizer',
                'website' => 'https://pfizer.com',
                'description' => 'شرکت آمریکایی تولیدکننده داروهای مختلف',
            ],
            [
                'name' => 'نوارتیس',
                'slug' => 'novartis',
                'website' => 'https://novartis.com',
                'description' => 'شرکت سوئیسی تولیدکننده داروهای مختلف',
            ],
            [
                'name' => 'روش',
                'slug' => 'roche',
                'website' => 'https://roche.com',
                'description' => 'شرکت سوئیسی تولیدکننده داروهای سرطان و تشخیص',
            ],
            [
                'name' => 'مرک',
                'slug' => 'merck',
                'website' => 'https://merck.com',
                'description' => 'شرکت آمریکایی-آلمانی تولیدکننده داروهای مختلف',
            ],
            [
                'name' => 'بایر',
                'slug' => 'bayer',
                'website' => 'https://bayer.com',
                'description' => 'شرکت آلمانی تولیدکننده داروهای مختلف',
            ],
            [
                'name' => 'آسترازنکا',
                'slug' => 'astrazeneca',
                'website' => 'https://astrazeneca.com',
                'description' => 'شرکت بریتانیایی-سوئدی تولیدکننده داروهای مختلف',
            ],
            [
                'name' => 'جانسون اند جانسون',
                'slug' => 'jj',
                'website' => 'https://jnj.com',
                'description' => 'شرکت آمریکایی تولیدکننده داروهای مختلف',
            ],

            // ============================================================
            // ✅ برندهای محصولات آرایشی و بهداشتی
            // ============================================================
            [
                'name' => 'مای (My)',
                'slug' => 'my',
                'website' => 'https://my-brand.com',
                'description' => 'برند محصولات آرایشی و بهداشتی مردانه',
            ],
            [
                'name' => 'ویتروس (Vitrus)',
                'slug' => 'vitrus',
                'website' => 'https://vitrus.com',
                'description' => 'برند محصولات مراقبت از مو',
            ],
            [
                'name' => 'مارگریت (Marguerite)',
                'slug' => 'marguerite',
                'website' => 'https://marguerite.com',
                'description' => 'برند محصولات آرایشی و بهداشتی زنانه',
            ],
            [
                'name' => 'بیول (Biol)',
                'slug' => 'biol',
                'website' => 'https://biol.com',
                'description' => 'برند محصولات مراقبت از مو با آرگان و کراتین',
            ],
            [
                'name' => 'هیدرودرم (Hydroderm)',
                'slug' => 'hydroderm',
                'website' => 'https://hydroderm.com',
                'description' => 'برند محصولات مراقبت از پوست و مو',
            ],
            [
                'name' => 'مارال (Maral)',
                'slug' => 'maral',
                'website' => 'https://maral.com',
                'description' => 'برند محصولات آرایشی و بهداشتی ایرانی',
            ],
        ];

        foreach ($brands as $data) {
            $data['tenant_id'] = $tenantId;

            Brand::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );

            $this->command->info("✅ برند: {$data['name']}");
        }

        $this->command->info(' ');
        $this->command->info('📊 خلاصه:');
        $this->command->info('   🏷️  مجموع برندها: ' . count($brands));
    }
}
