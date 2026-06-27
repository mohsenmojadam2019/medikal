<?php

namespace Database\Seeders;

use App\Models\LabCategory;
use App\Models\LabTest;
use Illuminate\Database\Seeder;

class LabSeeder extends Seeder
{
    public function run(): void
    {
        // ============================================================
        // CATEGORIES
        // ============================================================

        $categories = [
            ['name' => 'آزمایش خون', 'slug' => 'blood-test', 'icon' => '🩸'],
            ['name' => 'آزمایش ادرار', 'slug' => 'urine-test', 'icon' => '💧'],
            ['name' => 'آزمایش مدفوع', 'slug' => 'stool-test', 'icon' => '🧻'],
            ['name' => 'بیوشیمی', 'slug' => 'biochemistry', 'icon' => '🧪'],
            ['name' => 'هماتولوژی', 'slug' => 'hematology', 'icon' => '🩸'],
            ['name' => 'هورمون‌شناسی', 'slug' => 'hormonology', 'icon' => '🧬'],
            ['name' => 'میکروبیولوژی', 'slug' => 'microbiology', 'icon' => '🔬'],
            ['name' => 'سرولوژی', 'slug' => 'serology', 'icon' => '🧫'],
            ['name' => 'پاتولوژی', 'slug' => 'pathology', 'icon' => '🔬'],
            ['name' => 'ژنتیک', 'slug' => 'genetics', 'icon' => '🧬'],
        ];

        foreach ($categories as $cat) {
            LabCategory::updateOrCreate(
                ['slug' => $cat['slug']],
                $cat
            );
        }

        $this->command->info('✅ دسته‌بندی‌های آزمایشگاه ایجاد شدند.');

        // ============================================================
        // TESTS
        // ============================================================

        $bloodCat = LabCategory::where('slug', 'blood-test')->first();
        $biochemCat = LabCategory::where('slug', 'biochemistry')->first();
        $hormonCat = LabCategory::where('slug', 'hormonology')->first();
        $hematoCat = LabCategory::where('slug', 'hematology')->first();

        $tests = [
            // Blood
            [
                'category_id' => $bloodCat?->id,
                'name' => 'شمارش کامل خون (CBC)',
                'short_name' => 'CBC',
                'sample_type' => 'blood',
                'unit' => 'cells/µL',
                'min_range' => 4000,
                'max_range' => 10000,
                'price' => 150000,
                'turnaround_time' => 4,
                'requires_fasting' => false,
            ],
            [
                'category_id' => $biochemCat?->id,
                'name' => 'قند خون ناشتا (FBS)',
                'short_name' => 'FBS',
                'sample_type' => 'blood',
                'unit' => 'mg/dL',
                'min_range' => 70,
                'max_range' => 100,
                'critical_low' => 40,
                'critical_high' => 400,
                'price' => 80000,
                'turnaround_time' => 3,
                'requires_fasting' => true,
                'fasting_hours' => 8,
            ],
            [
                'category_id' => $biochemCat?->id,
                'name' => 'کلسترول تام',
                'short_name' => 'Cholesterol',
                'sample_type' => 'blood',
                'unit' => 'mg/dL',
                'min_range' => 120,
                'max_range' => 240,
                'critical_high' => 400,
                'price' => 90000,
                'turnaround_time' => 4,
                'requires_fasting' => true,
                'fasting_hours' => 12,
            ],
            [
                'category_id' => $biochemCat?->id,
                'name' => 'تری‌گلیسیرید',
                'short_name' => 'TG',
                'sample_type' => 'blood',
                'unit' => 'mg/dL',
                'min_range' => 50,
                'max_range' => 200,
                'critical_high' => 500,
                'price' => 85000,
                'turnaround_time' => 4,
                'requires_fasting' => true,
                'fasting_hours' => 12,
            ],
            [
                'category_id' => $hormonCat?->id,
                'name' => 'هورمون محرک تیروئید (TSH)',
                'short_name' => 'TSH',
                'sample_type' => 'blood',
                'unit' => 'mIU/L',
                'min_range' => 0.4,
                'max_range' => 4.0,
                'critical_low' => 0.1,
                'critical_high' => 20,
                'price' => 120000,
                'turnaround_time' => 6,
                'requires_fasting' => false,
            ],
            [
                'category_id' => $hormonCat?->id,
                'name' => 'تیروکسین آزاد (FT4)',
                'short_name' => 'FT4',
                'sample_type' => 'blood',
                'unit' => 'ng/dL',
                'min_range' => 0.8,
                'max_range' => 1.8,
                'price' => 130000,
                'turnaround_time' => 6,
                'requires_fasting' => false,
            ],
            [
                'category_id' => $hematoCat?->id,
                'name' => 'هموگلوبین (Hb)',
                'short_name' => 'Hb',
                'sample_type' => 'blood',
                'unit' => 'g/dL',
                'min_range' => 12,
                'max_range' => 16,
                'critical_low' => 6,
                'critical_high' => 20,
                'price' => 60000,
                'turnaround_time' => 2,
                'requires_fasting' => false,
            ],
            [
                'category_id' => $hematoCat?->id,
                'name' => 'گلبول‌های سفید (WBC)',
                'short_name' => 'WBC',
                'sample_type' => 'blood',
                'unit' => 'cells/µL',
                'min_range' => 4000,
                'max_range' => 10000,
                'critical_low' => 1000,
                'critical_high' => 30000,
                'price' => 70000,
                'turnaround_time' => 2,
                'requires_fasting' => false,
            ],
            [
                'category_id' => $hematoCat?->id,
                'name' => 'پلاکت (PLT)',
                'short_name' => 'PLT',
                'sample_type' => 'blood',
                'unit' => 'cells/µL',
                'min_range' => 150000,
                'max_range' => 400000,
                'critical_low' => 50000,
                'critical_high' => 800000,
                'price' => 70000,
                'turnaround_time' => 2,
                'requires_fasting' => false,
            ],
        ];

        foreach ($tests as $test) {
            LabTest::updateOrCreate(
                ['code' => $this->generateCode()],
                $test
            );
        }

        $this->command->info('✅ تست‌های آزمایشگاه ایجاد شدند.');
    }

    private function generateCode(): string
    {
        $prefix = 'LBT';
        $year = now()->format('y');
        $random = str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        return "{$prefix}-{$year}-{$random}";
    }
}
