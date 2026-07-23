<?php
// database/seeders/PharmaciesSeeder.php

namespace Database\Seeders;

use App\Models\Pharmacy;
use App\Models\Clinic;
use App\Models\Province;
use App\Models\City;
use Illuminate\Database\Seeder;

class PharmaciesSeeder extends Seeder
{
    public function run(): void
    {
        $clinics = Clinic::all()->keyBy('slug');
        $tehran = Province::where('name', 'تهران')->first();
        $tehranCity = City::where('name', 'تهران')->where('province_id', $tehran?->id)->first();
        $isfahan = Province::where('name', 'اصفهان')->first();
        $isfahanCity = City::where('name', 'اصفهان')->where('province_id', $isfahan?->id)->first();

        $pharmacies = [
            // ✅ داروخانه دکتر وب (متصل به کلینیک دکتر وب)
            [
                'name' => 'داروخانه دکتر وب',
                'license_number' => 'PH-001',
                'address' => 'تهران، خیابان ولیعصر، پلاک ۱۲۳',
                'phone' => '۰۲۱-۲۲۲۲۲۲۲۱',
                'email' => 'pharmacy@drweb.com',
                'province_id' => $tehran?->id,
                'city_id' => $tehranCity?->id,
                'latitude' => 35.6892,
                'longitude' => 51.3890,
                'clinic_slug' => 'dr-web',
                'is_active' => true,
                'is_online' => true,
            ],
            // ✅ داروخانه سلامت (متصل به کلینیک سلامت پارس)
            [
                'name' => 'داروخانه سلامت پارس',
                'license_number' => 'PH-002',
                'address' => 'تهران، خیابان انقلاب، پلاک ۴۵',
                'phone' => '۰۲۱-۳۳۳۳۳۳۳۱',
                'email' => 'pharmacy@salamatpars.com',
                'province_id' => $tehran?->id,
                'city_id' => $tehranCity?->id,
                'latitude' => 35.7000,
                'longitude' => 51.4000,
                'clinic_slug' => 'salamat-pars',
                'is_active' => true,
                'is_online' => true,
            ],
            // ✅ داروخانه مهرگان (متصل به کلینیک مهرگان)
            [
                'name' => 'داروخانه مهرگان',
                'license_number' => 'PH-003',
                'address' => 'اصفهان، خیابان چهارباغ، پلاک ۷۸',
                'phone' => '۰۳۱-۴۴۴۴۴۴۴۱',
                'email' => 'pharmacy@mehragan.com',
                'province_id' => $isfahan?->id,
                'city_id' => $isfahanCity?->id,
                'latitude' => 32.6546,
                'longitude' => 51.6680,
                'clinic_slug' => 'mehragan',
                'is_active' => true,
                'is_online' => true,
            ],
            // ✅ داروخانه امید (متصل به کلینیک امید)
            [
                'name' => 'داروخانه امید',
                'license_number' => 'PH-004',
                'address' => 'شیراز، خیابان زند، پلاک ۵۶',
                'phone' => '۰۷۱-۵۵۵۵۵۵۵۱',
                'email' => 'pharmacy@omid.com',
                'province_id' => Province::where('name', 'فارس')->first()?->id,
                'city_id' => City::where('name', 'شیراز')->where('province_id', Province::where('name', 'فارس')->first()?->id)->first()?->id,
                'latitude' => 29.5918,
                'longitude' => 52.5837,
                'clinic_slug' => 'omid',
                'is_active' => true,
                'is_online' => true,
            ],
            // ✅ داروخانه نور (متصل به کلینیک نور)
            [
                'name' => 'داروخانه نور',
                'license_number' => 'PH-005',
                'address' => 'مشهد، خیابان امام رضا، پلاک ۳۴',
                'phone' => '۰۵۱-۶۶۶۶۶۶۶۱',
                'email' => 'pharmacy@noor.com',
                'province_id' => Province::where('name', 'خراسان رضوی')->first()?->id,
                'city_id' => City::where('name', 'مشهد')->where('province_id', Province::where('name', 'خراسان رضوی')->first()?->id)->first()?->id,
                'latitude' => 36.2972,
                'longitude' => 59.6067,
                'clinic_slug' => 'noor',
                'is_active' => true,
                'is_online' => true,
            ],
            // ✅ داروخانه آتیه (متصل به کلینیک آتیه)
            [
                'name' => 'داروخانه آتیه',
                'license_number' => 'PH-006',
                'address' => 'تبریز، خیابان ولیعصر، پلاک ۱۲',
                'phone' => '۰۴۱-۷۷۷۷۷۷۷۱',
                'email' => 'pharmacy@atiyeh.com',
                'province_id' => Province::where('name', 'آذربایجان شرقی')->first()?->id,
                'city_id' => City::where('name', 'تبریز')->where('province_id', Province::where('name', 'آذربایجان شرقی')->first()?->id)->first()?->id,
                'latitude' => 38.0800,
                'longitude' => 46.2919,
                'clinic_slug' => 'atiyeh',
                'is_active' => true,
                'is_online' => true,
            ],
        ];

        foreach ($pharmacies as $data) {
            $clinic = $clinics[$data['clinic_slug']] ?? null;
            unset($data['clinic_slug']);

            Pharmacy::updateOrCreate(
                ['license_number' => $data['license_number']],
                array_merge($data, ['clinic_id' => $clinic?->id])
            );
        }

        $this->command->info('✅ ۶ داروخانه با موفقیت ایجاد شدند.');
    }
}
