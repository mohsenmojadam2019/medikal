<?php
// database/seeders/LabTestsSeeder.php

namespace Database\Seeders;

use App\Models\LabTest;
use App\Models\LabCategory;
use App\Models\Clinic;
use Illuminate\Database\Seeder;

class LabSeeder extends Seeder
{
    public function run(): void
    {
        // ✅ بررسی کن که جدول وجود داره یا نه
        if (!\Illuminate\Support\Facades\Schema::hasTable('lab_tests')) {
            $this->command->warn('⚠️ جدول lab_tests وجود ندارد!');
            return;
        }

        // ✅ بررسی کن که قبلاً داده وجود داره یا نه
        if (LabTest::count() > 0) {
            $this->command->info('⏭️ تست‌های آزمایشگاه قبلاً ایجاد شده‌اند.');
            return;
        }

        $clinics = Clinic::all()->keyBy('slug');
        $bloodCat = LabCategory::where('slug', 'blood-test')->first();
        $biochemCat = LabCategory::where('slug', 'biochemistry')->first();

        $tests = [
            // ... همه داده‌های قبلی (همون که داری)
        ];

        foreach ($tests as $data) {
            $clinic = $clinics[$data['clinic_slug']] ?? null;
            unset($data['clinic_slug']);

            LabTest::updateOrCreate(
                ['code' => $this->generateCode()],
                array_merge($data, ['clinic_id' => $clinic?->id])
            );
        }

        $this->command->info('✅ تست‌های آزمایشگاه با موفقیت ایجاد شدند.');
    }

    private function generateCode(): string
    {
        $prefix = 'LBT';
        $year = now()->format('y');
        $random = str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        return "{$prefix}-{$year}-{$random}";
    }
}
