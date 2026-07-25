<?php
// database/seeders/ImagingCentersSeeder.php

namespace Database\Seeders;

use App\Models\PACS\MedicalImage;
use App\Models\Clinic;
use App\Models\Province;
use App\Models\City;
use Illuminate\Database\Seeder;

class ImagingCentersSeeder extends Seeder
{
    public function run(): void
    {
        // ✅ اول بررسی کن که جدول وجود داره یا نه
        if (!\Illuminate\Support\Facades\Schema::hasTable('medical_images')) {
            $this->command->warn('⚠️ جدول medical_images وجود ندارد!');
            $this->command->warn('📌 لطفاً ابتدا Migration را اجرا کنید: php artisan migrate');
            return;
        }

        // ✅ بررسی کن که قبلاً داده وجود داره یا نه
        if (MedicalImage::count() > 0) {
            $this->command->info('⏭️ تصاویر پزشکی قبلاً ایجاد شده‌اند.');
            return;
        }

        $clinics = Clinic::all()->keyBy('slug');

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

        $imagingCenters = [
            // ... همه داده‌های قبلی (همون که داری)
        ];

        foreach ($imagingCenters as $data) {
            MedicalImage::updateOrCreate(
                [
                    'file_path' => $data['file_path'],
                ],
                $data
            );
        }

        $this->command->info('✅ ۶ مرکز تصویربرداری با موفقیت ایجاد شدند.');
    }
}
