<?php
// database/seeders/LabTestsSeeder.php

namespace Database\Seeders;

use App\Models\LabTest;
use App\Models\LabCategory;
use App\Models\Clinic;
use Illuminate\Database\Seeder;

class LabTestsSeeder extends Seeder
{
    public function run(): void
    {
        $clinics = Clinic::all()->keyBy('slug');
        $bloodCat = LabCategory::where('slug', 'blood-test')->first();
        $biochemCat = LabCategory::where('slug', 'biochemistry')->first();

        $tests = [
            // ✅ آزمایشگاه‌های دکتر وب (۲ تا)
            [
                'category_id' => $bloodCat?->id,
                'name' => 'شمارش کامل خون (CBC) - دکتر وب',
                'short_name' => 'CBC',
                'sample_type' => 'blood',
                'unit' => 'cells/µL',
                'min_range' => 4000,
                'max_range' => 10000,
                'price' => 150000,
                'turnaround_time' => 4,
                'requires_fasting' => false,
                'clinic_slug' => 'dr-web',
                'is_active' => true,
            ],
            [
                'category_id' => $biochemCat?->id,
                'name' => 'قند خون ناشتا - دکتر وب',
                'short_name' => 'FBS',
                'sample_type' => 'blood',
                'unit' => 'mg/dL',
                'min_range' => 70,
                'max_range' => 100,
                'price' => 80000,
                'turnaround_time' => 3,
                'requires_fasting' => true,
                'fasting_hours' => 8,
                'clinic_slug' => 'dr-web',
                'is_active' => true,
            ],
            // ✅ آزمایشگاه سلامت پارس
            [
                'category_id' => $bloodCat?->id,
                'name' => 'هموگلوبین - سلامت پارس',
                'short_name' => 'Hb',
                'sample_type' => 'blood',
                'unit' => 'g/dL',
                'min_range' => 12,
                'max_range' => 16,
                'price' => 60000,
                'turnaround_time' => 2,
                'requires_fasting' => false,
                'clinic_slug' => 'salamat-pars',
                'is_active' => true,
            ],
            // ✅ آزمایشگاه مهرگان
            [
                'category_id' => $biochemCat?->id,
                'name' => 'کلسترول تام - مهرگان',
                'short_name' => 'Cholesterol',
                'sample_type' => 'blood',
                'unit' => 'mg/dL',
                'min_range' => 120,
                'max_range' => 240,
                'price' => 90000,
                'turnaround_time' => 4,
                'requires_fasting' => true,
                'fasting_hours' => 12,
                'clinic_slug' => 'mehragan',
                'is_active' => true,
            ],
            // ✅ آزمایشگاه امید
            [
                'category_id' => $biochemCat?->id,
                'name' => 'تری‌گلیسیرید - امید',
                'short_name' => 'TG',
                'sample_type' => 'blood',
                'unit' => 'mg/dL',
                'min_range' => 50,
                'max_range' => 200,
                'price' => 85000,
                'turnaround_time' => 4,
                'requires_fasting' => true,
                'fasting_hours' => 12,
                'clinic_slug' => 'omid',
                'is_active' => true,
            ],
            // ✅ آزمایشگاه نور
            [
                'category_id' => $bloodCat?->id,
                'name' => 'پلاکت - نور',
                'short_name' => 'PLT',
                'sample_type' => 'blood',
                'unit' => 'cells/µL',
                'min_range' => 150000,
                'max_range' => 400000,
                'price' => 70000,
                'turnaround_time' => 2,
                'requires_fasting' => false,
                'clinic_slug' => 'noor',
                'is_active' => true,
            ],
        ];

        foreach ($tests as $data) {
            $clinic = $clinics[$data['clinic_slug']] ?? null;
            unset($data['clinic_slug']);

            LabTest::updateOrCreate(
                ['code' => $this->generateCode()],
                array_merge($data, ['clinic_id' => $clinic?->id])
            );
        }

        $this->command->info('✅ ۶ آزمایشگاه با موفقیت ایجاد شدند.');
    }

    private function generateCode(): string
    {
        $prefix = 'LBT';
        $year = now()->format('y');
        $random = str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        return "{$prefix}-{$year}-{$random}";
    }
}
