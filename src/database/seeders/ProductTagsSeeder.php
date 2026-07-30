<?php

// database/seeders/ProductTagsSeeder.php

namespace Database\Seeders;

use App\Models\ProductTag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductTagsSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = \App\Models\Tenant::first()?->id ?? 1;

        $tags = [
            // ============================================================
            // ✅ برچسب‌های تخفیف و فروش
            // ============================================================
            [
                'name' => 'تخفیف ویژه',
                'slug' => 'special-discount',
                'color' => '#FF6B6B',
            ],
            [
                'name' => 'تخفیف شگفت‌انگیز',
                'slug' => 'amazing-discount',
                'color' => '#F9CA24',
            ],
            [
                'name' => 'فروش ویژه',
                'slug' => 'flash-sale',
                'color' => '#FF4757',
            ],
            [
                'name' => 'حراج',
                'slug' => 'sale',
                'color' => '#FF6348',
            ],
            [
                'name' => 'پیشنهاد شگفت‌انگیز',
                'slug' => 'amazing-offer',
                'color' => '#F0932B',
            ],

            // ============================================================
            // ✅ برچسب‌های پرفروش و محبوب
            // ============================================================
            [
                'name' => 'پرفروش‌ترین',
                'slug' => 'best-seller',
                'color' => '#4ECDC4',
            ],
            [
                'name' => 'محبوب‌ترین',
                'slug' => 'most-popular',
                'color' => '#45B7D1',
            ],
            [
                'name' => 'پرفروش',
                'slug' => 'bestseller',
                'color' => '#6C5CE7',
            ],

            // ============================================================
            // ✅ برچسب‌های جدید
            // ============================================================
            [
                'name' => 'جدیدترین',
                'slug' => 'new',
                'color' => '#00B894',
            ],
            [
                'name' => 'محصول جدید',
                'slug' => 'new-product',
                'color' => '#00CEC9',
            ],
            [
                'name' => 'تازه وارد',
                'slug' => 'new-arrival',
                'color' => '#0984E3',
            ],

            // ============================================================
            // ✅ برچسب‌های ارسال و تحویل
            // ============================================================
            [
                'name' => 'ارسال رایگان',
                'slug' => 'free-shipping',
                'color' => '#6AB04C',
            ],
            [
                'name' => 'تحویل فوری',
                'slug' => 'express-delivery',
                'color' => '#FDCB6E',
            ],

            // ============================================================
            // ✅ برچسب‌های کیفیت و سلامت
            // ============================================================
            [
                'name' => 'ضد حساسیت',
                'slug' => 'hypoallergenic',
                'color' => '#BE2EDD',
            ],
            [
                'name' => 'بدون الکل',
                'slug' => 'alcohol-free',
                'color' => '#22A6B3',
            ],
            [
                'name' => 'تایید شده',
                'slug' => 'approved',
                'color' => '#7ED321',
            ],
            [
                'name' => 'تست شده',
                'slug' => 'tested',
                'color' => '#C4E538',
            ],
            [
                'name' => 'طبیعی',
                'slug' => 'natural',
                'color' => '#6AB04C',
            ],
            [
                'name' => 'ارگانیک',
                'slug' => 'organic',
                'color' => '#2ECC71',
            ],
            [
                'name' => 'حلال',
                'slug' => 'halal',
                'color' => '#27AE60',
            ],

            // ============================================================
            // ✅ برچسب‌های هدف
            // ============================================================
            [
                'name' => 'ویژه آقایان',
                'slug' => 'for-men',
                'color' => '#2E86DE',
            ],
            [
                'name' => 'ویژه بانوان',
                'slug' => 'for-women',
                'color' => '#FD79A8',
            ],
            [
                'name' => 'ویژه کودکان',
                'slug' => 'for-kids',
                'color' => '#FDCB6E',
            ],
            [
                'name' => 'مناسب ورزشکاران',
                'slug' => 'for-athletes',
                'color' => '#00B894',
            ],
        ];

        foreach ($tags as $data) {
            $data['tenant_id'] = $tenantId;

            ProductTag::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );

            $this->command->info("✅ برچسب: {$data['name']}");
        }

        $this->command->info(' ');
        $this->command->info('📊 خلاصه:');
        $this->command->info('   🏷️  مجموع برچسب‌ها: ' . count($tags));
    }
}
