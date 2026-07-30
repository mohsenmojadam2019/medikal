<?php

// database/seeders/ProductAttributesSeeder.php

namespace Database\Seeders;

use App\Models\ProductAttribute;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductAttributesSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = \App\Models\Tenant::first()?->id ?? 1;

        $attributes = [
            // ============================================================
            // ✅ ویژگی‌های محصولات آرایشی و بهداشتی
            // ============================================================
            [
                'name' => 'کارایی',
                'slug' => 'usage',
                'type' => 'select',
                'options' => ['حالت دهنده مو', 'مرطوب کننده', 'حجم دهنده', 'صاف کننده', 'براق کننده', 'ضد ریزش'],
                'is_filterable' => true,
            ],
            [
                'name' => 'فرم محصول',
                'slug' => 'product-form',
                'type' => 'select',
                'options' => ['ژل', 'کرم', 'موس', 'اسپری', 'پماد', 'شامپو', 'نرم کننده', 'ماسک'],
                'is_filterable' => true,
            ],
            [
                'name' => 'جنس محفظه',
                'slug' => 'container-material',
                'type' => 'select',
                'options' => ['پلاستیکی', 'شیشه‌ای', 'فلزی', 'چوبی', 'کاغذی'],
                'is_filterable' => false,
            ],
            [
                'name' => 'نوع محفظه',
                'slug' => 'container-type',
                'type' => 'select',
                'options' => ['تیوب', 'بطری', 'جا‌بیضی', 'قوطی', 'شیشه', 'ویال', 'پمپ'],
                'is_filterable' => false,
            ],

            // ============================================================
            // ✅ ویژگی‌های داروها
            // ============================================================
            [
                'name' => 'نوع دارو',
                'slug' => 'drug-type',
                'type' => 'select',
                'options' => ['شیمیایی', 'گیاهی', 'بیوتکنولوژی', 'هورمونی'],
                'is_filterable' => true,
            ],
            [
                'name' => 'روش مصرف',
                'slug' => 'administration-route',
                'type' => 'select',
                'options' => ['خوراکی', 'موضعی', 'تزریقی', 'استنشاقی', 'چشمی'],
                'is_filterable' => true,
            ],
            [
                'name' => 'دسته درمانی',
                'slug' => 'therapeutic-category',
                'type' => 'select',
                'options' => ['قلب و عروق', 'گوارش', 'تنفسی', 'اعصاب', 'دیابت', 'فشار خون', 'آنتی‌بیوتیک'],
                'is_filterable' => true,
            ],

            // ============================================================
            // ✅ ویژگی‌های مکمل‌ها
            // ============================================================
            [
                'name' => 'نوع مکمل',
                'slug' => 'supplement-type',
                'type' => 'select',
                'options' => ['ویتامین', 'معدنی', 'پروتئینی', 'گیاهی', 'اسید آمینه', 'امگا ۳'],
                'is_filterable' => true,
            ],
            [
                'name' => 'گروه هدف',
                'slug' => 'target-group',
                'type' => 'select',
                'options' => ['ورزشکاران', 'سالمندان', 'کودکان', 'بانوان باردار', 'عمومی'],
                'is_filterable' => true,
            ],

            // ============================================================
            // ✅ ویژگی‌های عمومی
            // ============================================================
            [
                'name' => 'کشور تولیدکننده',
                'slug' => 'country-of-origin',
                'type' => 'select',
                'options' => ['ایران', 'آلمان', 'فرانسه', 'ترکیه', 'کره جنوبی', 'چین', 'آمریکا', 'انگلیس', 'سوئیس', 'دانمارک'],
                'is_filterable' => true,
            ],
            [
                'name' => 'گارانتی',
                'slug' => 'warranty',
                'type' => 'select',
                'options' => ['۱۸ ماهه', '۲۴ ماهه', '۳۶ ماهه', 'فاقد گارانتی'],
                'is_filterable' => false,
            ],
            [
                'name' => 'حجم',
                'slug' => 'volume',
                'type' => 'text',
                'options' => null,
                'is_filterable' => false,
            ],
            [
                'name' => 'وزن',
                'slug' => 'weight',
                'type' => 'text',
                'options' => null,
                'is_filterable' => false,
            ],
            [
                'name' => 'ابعاد',
                'slug' => 'dimensions',
                'type' => 'text',
                'options' => null,
                'is_filterable' => false,
            ],
        ];

        foreach ($attributes as $data) {
            $data['tenant_id'] = $tenantId;

            ProductAttribute::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );

            $this->command->info("✅ ویژگی: {$data['name']}");
        }

        $this->command->info(' ');
        $this->command->info('📊 خلاصه:');
        $this->command->info('   🏷️  مجموع ویژگی‌ها: ' . count($attributes));
    }
}
