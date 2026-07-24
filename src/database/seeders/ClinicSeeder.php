<?php
// database/seeders/ClinicsSeeder.php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\Province;
use App\Models\City;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ClinicsSeeder extends Seeder
{
    public function run(): void
    {
        $tehran = Province::where('name', 'تهران')->first();
        $tehranCity = City::where('name', 'تهران')->where('province_id', $tehran?->id)->first();

        $isfahan = Province::where('name', 'اصفهان')->first();
        $isfahanCity = City::where('name', 'اصفهان')->where('province_id', $isfahan?->id)->first();

        $shiraz = Province::where('name', 'فارس')->first();
        $shirazCity = City::where('name', 'شیراز')->where('province_id', $shiraz?->id)->first();

        $mashhad = Province::where('name', 'خراسان رضوی')->first();
        $mashhadCity = City::where('name', 'مشهد')->where('province_id', $mashhad?->id)->first();

        $tabriz = Province::where('name', 'آذربایجان شرقی')->first();
        $tabrizCity = City::where('name', 'تبریز')->where('province_id', $tabriz?->id)->first();

        $clinics = [
            [
                'name' => 'کلینیک دکتر وب',
                'slug' => 'dr-web',
                'address' => 'تهران، خیابان ولیعصر، پلاک ۱۲۳',
                'phone' => '۰۲۱-۲۲۲۲۲۲۲۲',
                'email' => 'info@drweb.com',
                'website' => 'https://drweb.com',
                'province_id' => $tehran?->id,
                'city_id' => $tehranCity?->id,
                'latitude' => 35.6892,
                'longitude' => 51.3890,
                'timezone' => 'Asia/Tehran',
                'currency' => 'تومان',
                'language' => 'fa',
                'tax_rate' => 9,
                'invoice_prefix' => 'DW',
                'appointment_prefix' => 'DW-APP',
                'primary_color' => '#2563eb',
                'secondary_color' => '#7c3aed',
                'theme' => 'default',
                'is_active' => true,
                'is_verified' => true,
                'webhook_enabled' => false,
                'webhook_secret' => null,
                'webhook_logs' => null,
                'metadata' => null,
                'settings' => [
                    'appointment_reminder_hours' => 24,
                    'enable_online_payment' => true,
                    'enable_telemedicine' => true,
                ],
            ],
            [
                'name' => 'کلینیک سلامت پارس',
                'slug' => 'salamat-pars',
                'address' => 'تهران، خیابان انقلاب، پلاک ۴۵',
                'phone' => '۰۲۱-۳۳۳۳۳۳۳۳',
                'email' => 'info@salamatpars.com',
                'website' => 'https://salamatpars.com',
                'province_id' => $tehran?->id,
                'city_id' => $tehranCity?->id,
                'latitude' => 35.7000,
                'longitude' => 51.4000,
                'timezone' => 'Asia/Tehran',
                'currency' => 'تومان',
                'language' => 'fa',
                'tax_rate' => 9,
                'invoice_prefix' => 'SP',
                'appointment_prefix' => 'SP-APP',
                'primary_color' => '#10b981',
                'secondary_color' => '#059669',
                'theme' => 'default',
                'is_active' => true,
                'is_verified' => true,
                'webhook_enabled' => false,
                'webhook_secret' => null,
                'webhook_logs' => null,
                'metadata' => null,
                'settings' => [
                    'appointment_reminder_hours' => 24,
                    'enable_online_payment' => true,
                    'enable_telemedicine' => true,
                ],
            ],
            [
                'name' => 'کلینیک مهرگان',
                'slug' => 'mehragan',
                'address' => 'اصفهان، خیابان چهارباغ، پلاک ۷۸',
                'phone' => '۰۳۱-۴۴۴۴۴۴۴۴',
                'email' => 'info@mehragan.com',
                'website' => 'https://mehragan.com',
                'province_id' => $isfahan?->id,
                'city_id' => $isfahanCity?->id,
                'latitude' => 32.6546,
                'longitude' => 51.6680,
                'timezone' => 'Asia/Tehran',
                'currency' => 'تومان',
                'language' => 'fa',
                'tax_rate' => 9,
                'invoice_prefix' => 'MG',
                'appointment_prefix' => 'MG-APP',
                'primary_color' => '#f59e0b',
                'secondary_color' => '#d97706',
                'theme' => 'default',
                'is_active' => true,
                'is_verified' => true,
                'webhook_enabled' => false,
                'webhook_secret' => null,
                'webhook_logs' => null,
                'metadata' => null,
                'settings' => [
                    'appointment_reminder_hours' => 24,
                    'enable_online_payment' => true,
                    'enable_telemedicine' => true,
                ],
            ],
            [
                'name' => 'کلینیک امید',
                'slug' => 'omid',
                'address' => 'شیراز، خیابان زند، پلاک ۵۶',
                'phone' => '۰۷۱-۵۵۵۵۵۵۵۵',
                'email' => 'info@omid.com',
                'website' => 'https://omid.com',
                'province_id' => $shiraz?->id,
                'city_id' => $shirazCity?->id,
                'latitude' => 29.5918,
                'longitude' => 52.5837,
                'timezone' => 'Asia/Tehran',
                'currency' => 'تومان',
                'language' => 'fa',
                'tax_rate' => 9,
                'invoice_prefix' => 'OM',
                'appointment_prefix' => 'OM-APP',
                'primary_color' => '#8b5cf6',
                'secondary_color' => '#6d28d9',
                'theme' => 'default',
                'is_active' => true,
                'is_verified' => true,
                'webhook_enabled' => false,
                'webhook_secret' => null,
                'webhook_logs' => null,
                'metadata' => null,
                'settings' => [
                    'appointment_reminder_hours' => 24,
                    'enable_online_payment' => true,
                    'enable_telemedicine' => true,
                ],
            ],
            [
                'name' => 'کلینیک نور',
                'slug' => 'noor',
                'address' => 'مشهد، خیابان امام رضا، پلاک ۳۴',
                'phone' => '۰۵۱-۶۶۶۶۶۶۶۶',
                'email' => 'info@noor.com',
                'website' => 'https://noor.com',
                'province_id' => $mashhad?->id,
                'city_id' => $mashhadCity?->id,
                'latitude' => 36.2972,
                'longitude' => 59.6067,
                'timezone' => 'Asia/Tehran',
                'currency' => 'تومان',
                'language' => 'fa',
                'tax_rate' => 9,
                'invoice_prefix' => 'NO',
                'appointment_prefix' => 'NO-APP',
                'primary_color' => '#06b6d4',
                'secondary_color' => '#0891b2',
                'theme' => 'default',
                'is_active' => true,
                'is_verified' => true,
                'webhook_enabled' => false,
                'webhook_secret' => null,
                'webhook_logs' => null,
                'metadata' => null,
                'settings' => [
                    'appointment_reminder_hours' => 24,
                    'enable_online_payment' => true,
                    'enable_telemedicine' => true,
                ],
            ],
            [
                'name' => 'کلینیک آتیه',
                'slug' => 'atiyeh',
                'address' => 'تبریز، خیابان ولیعصر، پلاک ۱۲',
                'phone' => '۰۴۱-۷۷۷۷۷۷۷۷',
                'email' => 'info@atiyeh.com',
                'website' => 'https://atiyeh.com',
                'province_id' => $tabriz?->id,
                'city_id' => $tabrizCity?->id,
                'latitude' => 38.0800,
                'longitude' => 46.2919,
                'timezone' => 'Asia/Tehran',
                'currency' => 'تومان',
                'language' => 'fa',
                'tax_rate' => 9,
                'invoice_prefix' => 'AT',
                'appointment_prefix' => 'AT-APP',
                'primary_color' => '#ef4444',
                'secondary_color' => '#dc2626',
                'theme' => 'default',
                'is_active' => true,
                'is_verified' => true,
                'webhook_enabled' => false,
                'webhook_secret' => null,
                'webhook_logs' => null,
                'metadata' => null,
                'settings' => [
                    'appointment_reminder_hours' => 24,
                    'enable_online_payment' => true,
                    'enable_telemedicine' => true,
                ],
            ],
        ];

        foreach ($clinics as $clinicData) {
            $clinic = Clinic::updateOrCreate(
                ['slug' => $clinicData['slug']],
                $clinicData
            );

            // ============================================
            // ✅ آپلود لوگو و favicon با Media Library
            // ============================================

            // آپلود لوگو (اگر فایل موجود باشد)
            $logoPath = public_path('images/clinics/' . $clinic->slug . '/logo.png');
            if (file_exists($logoPath)) {
                $clinic->addMedia($logoPath)
                    ->preservingOriginal()
                    ->toMediaCollection('logo');
                $this->command->info("✅ لوگو برای {$clinic->name} آپلود شد");
            }

            // آپلود favicon (اگر فایل موجود باشد)
            $faviconPath = public_path('images/clinics/' . $clinic->slug . '/favicon.ico');
            if (file_exists($faviconPath)) {
                $clinic->addMedia($faviconPath)
                    ->preservingOriginal()
                    ->toMediaCollection('favicon');
                $this->command->info("✅ فاوآیکون برای {$clinic->name} آپلود شد");
            }
        }

        $this->command->info('✅ ۶ کلینیک با موفقیت ایجاد شدند.');
        $this->command->info('📊 لیست کلینیک‌ها:');
        $this->command->info('   1. کلینیک دکتر وب (تهران)');
        $this->command->info('   2. کلینیک سلامت پارس (تهران)');
        $this->command->info('   3. کلینیک مهرگان (اصفهان)');
        $this->command->info('   4. کلینیک امید (شیراز)');
        $this->command->info('   5. کلینیک نور (مشهد)');
        $this->command->info('   6. کلینیک آتیه (تبریز)');
    }
}
