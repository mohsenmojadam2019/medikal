<?php
// database/seeders/ClinicsSeeder.php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\Province;
use App\Models\City;
use Illuminate\Database\Seeder;

class ClinicSeeder extends Seeder
{
    public function run(): void
    {
        $tehran = Province::where('name', 'تهران')->first();
        $tehranCity = City::where('name', 'تهران')->where('province_id', $tehran?->id)->first();

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
                'timezone' => 'Asia/Tehran',
                'currency' => 'تومان',
                'language' => 'fa',
                'tax_rate' => 9,
                'invoice_prefix' => 'DW',
                'appointment_prefix' => 'DW-APP',
                'primary_color' => '#2563eb',
                'secondary_color' => '#7c3aed',
                'is_active' => true,
                'is_verified' => true,
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
                'timezone' => 'Asia/Tehran',
                'currency' => 'تومان',
                'language' => 'fa',
                'tax_rate' => 9,
                'invoice_prefix' => 'SP',
                'appointment_prefix' => 'SP-APP',
                'primary_color' => '#10b981',
                'secondary_color' => '#059669',
                'is_active' => true,
                'is_verified' => true,
            ],
            [
                'name' => 'کلینیک مهرگان',
                'slug' => 'mehragan',
                'address' => 'اصفهان، خیابان چهارباغ، پلاک ۷۸',
                'phone' => '۰۳۱-۴۴۴۴۴۴۴۴',
                'email' => 'info@mehragan.com',
                'website' => 'https://mehragan.com',
                'province_id' => Province::where('name', 'اصفهان')->first()?->id,
                'city_id' => City::where('name', 'اصفهان')->first()?->id,
                'timezone' => 'Asia/Tehran',
                'currency' => 'تومان',
                'language' => 'fa',
                'tax_rate' => 9,
                'invoice_prefix' => 'MG',
                'appointment_prefix' => 'MG-APP',
                'primary_color' => '#f59e0b',
                'secondary_color' => '#d97706',
                'is_active' => true,
                'is_verified' => true,
            ],
            [
                'name' => 'کلینیک امید',
                'slug' => 'omid',
                'address' => 'شیراز، خیابان زند، پلاک ۵۶',
                'phone' => '۰۷۱-۵۵۵۵۵۵۵۵',
                'email' => 'info@omid.com',
                'website' => 'https://omid.com',
                'province_id' => Province::where('name', 'فارس')->first()?->id,
                'city_id' => City::where('name', 'شیراز')->first()?->id,
                'timezone' => 'Asia/Tehran',
                'currency' => 'تومان',
                'language' => 'fa',
                'tax_rate' => 9,
                'invoice_prefix' => 'OM',
                'appointment_prefix' => 'OM-APP',
                'primary_color' => '#8b5cf6',
                'secondary_color' => '#6d28d9',
                'is_active' => true,
                'is_verified' => true,
            ],
            [
                'name' => 'کلینیک نور',
                'slug' => 'noor',
                'address' => 'مشهد، خیابان امام رضا، پلاک ۳۴',
                'phone' => '۰۵۱-۶۶۶۶۶۶۶۶',
                'email' => 'info@noor.com',
                'website' => 'https://noor.com',
                'province_id' => Province::where('name', 'خراسان رضوی')->first()?->id,
                'city_id' => City::where('name', 'مشهد')->first()?->id,
                'timezone' => 'Asia/Tehran',
                'currency' => 'تومان',
                'language' => 'fa',
                'tax_rate' => 9,
                'invoice_prefix' => 'NO',
                'appointment_prefix' => 'NO-APP',
                'primary_color' => '#06b6d4',
                'secondary_color' => '#0891b2',
                'is_active' => true,
                'is_verified' => true,
            ],
            [
                'name' => 'کلینیک آتیه',
                'slug' => 'atiyeh',
                'address' => 'تبریز، خیابان ولیعصر، پلاک ۱۲',
                'phone' => '۰۴۱-۷۷۷۷۷۷۷۷',
                'email' => 'info@atiyeh.com',
                'website' => 'https://atiyeh.com',
                'province_id' => Province::where('name', 'آذربایجان شرقی')->first()?->id,
                'city_id' => City::where('name', 'تبریز')->first()?->id,
                'timezone' => 'Asia/Tehran',
                'currency' => 'تومان',
                'language' => 'fa',
                'tax_rate' => 9,
                'invoice_prefix' => 'AT',
                'appointment_prefix' => 'AT-APP',
                'primary_color' => '#ef4444',
                'secondary_color' => '#dc2626',
                'is_active' => true,
                'is_verified' => true,
            ],
        ];

        foreach ($clinics as $clinic) {
            Clinic::updateOrCreate(
                ['slug' => $clinic['slug']],
                $clinic
            );
        }

        $this->command->info('✅ ۶ کلینیک با موفقیت ایجاد شدند.');
    }
}
