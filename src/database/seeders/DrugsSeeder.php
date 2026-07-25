<?php
// database/seeders/DrugsSeeder.php

namespace Database\Seeders;

use App\Models\Drug;
use App\Models\Pharmacy;
use Illuminate\Database\Seeder;

class DrugsSeeder extends Seeder
{
    public function run(): void
    {
        // دریافت تمام داروخانه‌ها
        $pharmacies = Pharmacy::all()->keyBy('name');

        // ✅ دریافت tenant_id پیش‌فرض
        $tenantId = \App\Models\Tenant::first()?->id ?? 1;

        $drugs = [
            // ============================================================
            // ✅ داروهای داروخانه دکتر وب
            // ============================================================
            [
                'pharmacy_name' => 'داروخانه دکتر وب',
                'name' => 'آموکسی‌سیلین ۵۰۰ میلی‌گرم',
                'generic_name' => 'آموکسی‌سیلین',
                'category' => 'آنتی‌بیوتیک',
                'form' => 'کپسول',
                'strength' => '۵۰۰ میلی‌گرم',
                'manufacturer' => 'داروسازی لقمان',
                'requires_prescription' => true,
                'price' => 45000,
                'stock' => 150,
                'is_active' => true,
            ],
            [
                'pharmacy_name' => 'داروخانه دکتر وب',
                'name' => 'استامینوفن ۵۰۰ میلی‌گرم',
                'generic_name' => 'استامینوفن',
                'category' => 'مسکن',
                'form' => 'قرص',
                'strength' => '۵۰۰ میلی‌گرم',
                'manufacturer' => 'داروسازی البرز',
                'requires_prescription' => false,
                'price' => 15000,
                'stock' => 300,
                'is_active' => true,
            ],
            [
                'pharmacy_name' => 'داروخانه دکتر وب',
                'name' => 'لوزارتان ۵۰ میلی‌گرم',
                'generic_name' => 'لوزارتان',
                'category' => 'فشار خون',
                'form' => 'قرص',
                'strength' => '۵۰ میلی‌گرم',
                'manufacturer' => 'داروسازی سیناژن',
                'requires_prescription' => true,
                'price' => 78000,
                'stock' => 80,
                'is_active' => true,
            ],
            [
                'pharmacy_name' => 'داروخانه دکتر وب',
                'name' => 'متفورمین ۵۰۰ میلی‌گرم',
                'generic_name' => 'متفورمین',
                'category' => 'دیابت',
                'form' => 'قرص',
                'strength' => '۵۰۰ میلی‌گرم',
                'manufacturer' => 'داروسازی فارابی',
                'requires_prescription' => true,
                'price' => 32000,
                'stock' => 120,
                'is_active' => true,
            ],

            // ============================================================
            // ✅ داروهای داروخانه سلامت پارس
            // ============================================================
            [
                'pharmacy_name' => 'داروخانه سلامت پارس',
                'name' => 'ایبوپروفن ۴۰۰ میلی‌گرم',
                'generic_name' => 'ایبوپروفن',
                'category' => 'ضدالتهاب',
                'form' => 'قرص',
                'strength' => '۴۰۰ میلی‌گرم',
                'manufacturer' => 'داروسازی حکیم',
                'requires_prescription' => false,
                'price' => 28000,
                'stock' => 200,
                'is_active' => true,
            ],
            [
                'pharmacy_name' => 'داروخانه سلامت پار스',
                'name' => 'آتنولول ۵۰ میلی‌گرم',
                'generic_name' => 'آتنولول',
                'category' => 'قلب و عروق',
                'form' => 'قرص',
                'strength' => '۵۰ میلی‌گرم',
                'manufacturer' => 'داروسازی ایران',
                'requires_prescription' => true,
                'price' => 65000,
                'stock' => 60,
                'is_active' => true,
            ],
            [
                'pharmacy_name' => 'داروخانه سلامت پارس',
                'name' => 'امپرازول ۲۰ میلی‌گرم',
                'generic_name' => 'امپرازول',
                'category' => 'معده',
                'form' => 'کپسول',
                'strength' => '۲۰ میلی‌گرم',
                'manufacturer' => 'داروسازی رازک',
                'requires_prescription' => false,
                'price' => 35000,
                'stock' => 150,
                'is_active' => true,
            ],

            // ============================================================
            // ✅ داروهای داروخانه مهرگان
            // ============================================================
            [
                'pharmacy_name' => 'داروخانه مهرگان',
                'name' => 'سیپروفلوکساسین ۵۰۰ میلی‌گرم',
                'generic_name' => 'سیپروفلوکساسین',
                'category' => 'آنتی‌بیوتیک',
                'form' => 'قرص',
                'strength' => '۵۰۰ میلی‌گرم',
                'manufacturer' => 'داروسازی ابوریحان',
                'requires_prescription' => true,
                'price' => 52000,
                'stock' => 90,
                'is_active' => true,
            ],
            [
                'pharmacy_name' => 'داروخانه مهرگان',
                'name' => 'دیازپام ۵ میلی‌گرم',
                'generic_name' => 'دیازپام',
                'category' => 'آرام‌بخش',
                'form' => 'قرص',
                'strength' => '۵ میلی‌گرم',
                'manufacturer' => 'داروسازی شهید قاضی',
                'requires_prescription' => true,
                'price' => 42000,
                'stock' => 40,
                'is_active' => true,
            ],
            [
                'pharmacy_name' => 'داروخانه مهرگان',
                'name' => 'ویتامین C ۱۰۰۰ میلی‌گرم',
                'generic_name' => 'ویتامین C',
                'category' => 'مکمل',
                'form' => 'قرص جوشان',
                'strength' => '۱۰۰۰ میلی‌گرم',
                'manufacturer' => 'داروسازی ویتانا',
                'requires_prescription' => false,
                'price' => 55000,
                'stock' => 250,
                'is_active' => true,
            ],

            // ============================================================
            // ✅ داروهای داروخانه امید
            // ============================================================
            [
                'pharmacy_name' => 'داروخانه امید',
                'name' => 'لوراتادین ۱۰ میلی‌گرم',
                'generic_name' => 'لوراتادین',
                'category' => 'آنتی‌هیستامین',
                'form' => 'قرص',
                'strength' => '۱۰ میلی‌گرم',
                'manufacturer' => 'داروسازی پورسینا',
                'requires_prescription' => false,
                'price' => 25000,
                'stock' => 180,
                'is_active' => true,
            ],
            [
                'pharmacy_name' => 'داروخانه امید',
                'name' => 'سالبوتامول ۱۰۰ میکروگرم',
                'generic_name' => 'سالبوتامول',
                'category' => 'تنفسی',
                'form' => 'اسپری',
                'strength' => '۱۰۰ میکروگرم',
                'manufacturer' => 'داروسازی آریا',
                'requires_prescription' => true,
                'price' => 85000,
                'stock' => 30,
                'is_active' => true,
            ],

            // ============================================================
            // ✅ داروهای داروخانه نور
            // ============================================================
            [
                'pharmacy_name' => 'داروخانه نور',
                'name' => 'بیساکودیل ۵ میلی‌گرم',
                'generic_name' => 'بیساکودیل',
                'category' => 'ملین',
                'form' => 'قرص',
                'strength' => '۵ میلی‌گرم',
                'manufacturer' => 'داروسازی حیات',
                'requires_prescription' => false,
                'price' => 18000,
                'stock' => 120,
                'is_active' => true,
            ],
            [
                'pharmacy_name' => 'داروخانه نور',
                'name' => 'آمانتادین ۱۰۰ میلی‌گرم',
                'generic_name' => 'آمانتادین',
                'category' => 'پارکینسون',
                'form' => 'کپسول',
                'strength' => '۱۰۰ میلی‌گرم',
                'manufacturer' => 'داروسازی البرز',
                'requires_prescription' => true,
                'price' => 62000,
                'stock' => 45,
                'is_active' => true,
            ],

            // ============================================================
            // ✅ داروهای داروخانه آتیه
            // ============================================================
            [
                'pharmacy_name' => 'داروخانه آتیه',
                'name' => 'کاربامازپین ۲۰۰ میلی‌گرم',
                'generic_name' => 'کاربامازپین',
                'category' => 'تشنج',
                'form' => 'قرص',
                'strength' => '۲۰۰ میلی‌گرم',
                'manufacturer' => 'داروسازی سیناژن',
                'requires_prescription' => true,
                'price' => 48000,
                'stock' => 55,
                'is_active' => true,
            ],
            [
                'pharmacy_name' => 'داروخانه آتیه',
                'name' => 'فولیک اسید ۱ میلی‌گرم',
                'generic_name' => 'فولیک اسید',
                'category' => 'مکمل',
                'form' => 'قرص',
                'strength' => '۱ میلی‌گرم',
                'manufacturer' => 'داروسازی ویتانا',
                'requires_prescription' => false,
                'price' => 12000,
                'stock' => 300,
                'is_active' => true,
            ],
        ];

        foreach ($drugs as $data) {
            $pharmacy = $pharmacies[$data['pharmacy_name']] ?? null;
            unset($data['pharmacy_name']);

            // ✅ تنظیم pharmacy_id و tenant_id
            if ($pharmacy) {
                $data['pharmacy_id'] = $pharmacy->id;
            }
            $data['tenant_id'] = $tenantId;

            Drug::updateOrCreate(
                [
                    'code' => $this->generateCode(),
                ],
                $data
            );
        }

        $this->command->info('✅ داروها با موفقیت ایجاد شدند.');
        $this->command->info('   📊 توزیع داروها:');
        $this->command->info('      🏥 دکتر وب: ۴ دارو');
        $this->command->info('      🏥 سلامت پارس: ۳ دارو');
        $this->command->info('      🏥 مهرگان: ۳ دارو');
        $this->command->info('      🏥 امید: ۲ دارو');
        $this->command->info('      🏥 نور: ۲ دارو');
        $this->command->info('      🏥 آتیه: ۲ دارو');
        $this->command->info('   📦 مجموع: ۱۶ دارو');
    }

    private function generateCode(): string
    {
        $prefix = 'DRG';
        $year = now()->format('y');
        $month = now()->format('m');
        $random = str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        return "{$prefix}-{$year}{$month}-{$random}";
    }
}
