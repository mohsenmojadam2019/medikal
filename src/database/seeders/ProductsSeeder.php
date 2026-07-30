<?php
// database/seeders/ProductsSeeder.php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Pharmacy;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductsSeeder extends Seeder
{
    public function run(): void
    {
        // ✅ دریافت Tenant پیش‌فرض
        $tenantId = \App\Models\Tenant::first()?->id ?? 1;

        // ✅ دریافت تمام داروخانه‌ها
        $pharmacies = Pharmacy::all()->keyBy('slug');

        // ✅ دریافت یا ایجاد دسته‌بندی‌ها
        $categories = $this->createCategories($tenantId);

        $products = [
            // ============================================================
            // ✅ داروهای داروخانه دکتر وب
            // ============================================================
            [
                'pharmacy_slug' => 'dr-web',
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
                'category_slug' => 'antibiotics',
            ],
            [
                'pharmacy_slug' => 'dr-web',
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
                'category_slug' => 'pain-relief',
            ],
            [
                'pharmacy_slug' => 'dr-web',
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
                'category_slug' => 'hypertension',
            ],
            [
                'pharmacy_slug' => 'dr-web',
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
                'category_slug' => 'diabetes',
            ],

            // ============================================================
            // ✅ داروهای داروخانه سلامت پارس
            // ============================================================
            [
                'pharmacy_slug' => 'salamat-pars',
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
                'category_slug' => 'anti-inflammatory',
            ],
            [
                'pharmacy_slug' => 'salamat-pars',
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
                'category_slug' => 'cardiovascular',
            ],
            [
                'pharmacy_slug' => 'salamat-pars',
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
                'category_slug' => 'digestive',
            ],

            // ============================================================
            // ✅ داروهای داروخانه مهرگان
            // ============================================================
            [
                'pharmacy_slug' => 'mehragan',
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
                'category_slug' => 'antibiotics',
            ],
            [
                'pharmacy_slug' => 'mehragan',
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
                'category_slug' => 'sedative',
            ],
            [
                'pharmacy_slug' => 'mehragan',
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
                'category_slug' => 'supplements',
            ],

            // ============================================================
            // ✅ داروهای داروخانه امید
            // ============================================================
            [
                'pharmacy_slug' => 'omid',
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
                'category_slug' => 'antihistamine',
            ],
            [
                'pharmacy_slug' => 'omid',
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
                'category_slug' => 'respiratory',
            ],

            // ============================================================
            // ✅ داروهای داروخانه نور
            // ============================================================
            [
                'pharmacy_slug' => 'noor',
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
                'category_slug' => 'laxative',
            ],
            [
                'pharmacy_slug' => 'noor',
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
                'category_slug' => 'parkinson',
            ],

            // ============================================================
            // ✅ داروهای داروخانه آتیه
            // ============================================================
            [
                'pharmacy_slug' => 'atiyeh',
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
                'category_slug' => 'seizure',
            ],
            [
                'pharmacy_slug' => 'atiyeh',
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
                'category_slug' => 'supplements',
            ],
        ];

        foreach ($products as $data) {
            $pharmacy = $pharmacies[$data['pharmacy_slug']] ?? null;
            $categorySlug = $data['category_slug'] ?? null;

            unset($data['pharmacy_slug']);
            unset($data['category_slug']);

            // ✅ تنظیم pharmacy_id و tenant_id
            if ($pharmacy) {
                $data['pharmacy_id'] = $pharmacy->id;
            }
            $data['tenant_id'] = $tenantId;

            // ✅ ایجاد کد منحصر‌به‌فرد
            $code = $this->generateCode();
            $data['code'] = $code;

            // ✅ ایجاد اسلاگ
            $data['slug'] = Str::slug($data['name']) . '-' . Str::random(4);

            try {
                // ✅ ایجاد محصول
                $product = Product::create($data);

                $this->command->info("✅ محصول: {$product->name} - {$product->generic_name}");

                // ✅ اتصال محصول به دسته‌بندی
                if ($categorySlug && isset($categories[$categorySlug])) {
                    $product->categories()->attach([
                        $categories[$categorySlug]->id => ['is_primary' => true]
                    ]);
                    $this->command->info("   └─ 🔗 متصل شد به دسته: {$categories[$categorySlug]->name}");
                }

            } catch (\Exception $e) {
                $this->command->error("❌ خطا در ایجاد محصول: {$data['name']} - " . $e->getMessage());
                continue;
            }
        }

        $this->command->info(' ');
        $this->command->info('📊 خلاصه:');
        $this->command->info('   📦 مجموع محصولات: ' . count($products));
        $this->command->info('   🏷️  دسته‌بندی‌ها: ' . count($categories));
    }

    /**
     * ✅ ایجاد دسته‌بندی‌های اصلی
     */
    private function createCategories($tenantId): array
    {
        $categories = [];

        // ============================================================
        // دسته‌بندی سطح اول: داروها
        // ============================================================
        $medications = ProductCategory::updateOrCreate(
            ['slug' => 'medications', 'tenant_id' => $tenantId],
            [
                'name' => '💊 داروها',
                'description' => 'تمامی داروهای با نسخه و بدون نسخه',
                'parent_id' => null,
                'is_active' => true,
                'is_featured' => true,
                'order' => 1,
            ]
        );
        $categories['medications'] = $medications;

        // ============================================================
        // دسته‌بندی سطح دوم: داروهای با نسخه
        // ============================================================
        $prescription = ProductCategory::updateOrCreate(
            ['slug' => 'prescription-products', 'tenant_id' => $tenantId],
            [
                'name' => '💊 داروهای با نسخه',
                'description' => 'داروهایی که نیاز به نسخه پزشک دارند',
                'parent_id' => $medications->id,
                'is_active' => true,
                'is_featured' => true,
                'order' => 1,
            ]
        );
        $categories['prescription'] = $prescription;

        // ============================================================
        // دسته‌بندی سطح دوم: داروهای آزاد (OTC)
        // ============================================================
        $otc = ProductCategory::updateOrCreate(
            ['slug' => 'otc-products', 'tenant_id' => $tenantId],
            [
                'name' => '💊 داروهای آزاد (OTC)',
                'description' => 'داروهای بدون نیاز به نسخه پزشک',
                'parent_id' => $medications->id,
                'is_active' => true,
                'is_featured' => true,
                'order' => 2,
            ]
        );
        $categories['otc'] = $otc;

        // ============================================================
        // دسته‌بندی سطح سوم: زیردسته‌های دارویی
        // ============================================================
        $subCategories = [
            [
                'slug' => 'antibiotics',
                'name' => '💊 آنتی‌بیوتیک‌ها',
                'parent' => 'prescription',
                'description' => 'داروهای ضدباکتری',
            ],
            [
                'slug' => 'hypertension',
                'name' => '💊 داروهای فشار خون',
                'parent' => 'prescription',
                'description' => 'داروهای کنترل فشار خون',
            ],
            [
                'slug' => 'diabetes',
                'name' => '💊 داروهای دیابت',
                'parent' => 'prescription',
                'description' => 'داروهای کنترل قند خون',
            ],
            [
                'slug' => 'cardiovascular',
                'name' => '💊 داروهای قلبی',
                'parent' => 'prescription',
                'description' => 'داروهای قلب و عروق',
            ],
            [
                'slug' => 'pain-relief',
                'name' => '💊 مسکن‌ها',
                'parent' => 'otc',
                'description' => 'داروهای ضد درد',
            ],
            [
                'slug' => 'anti-inflammatory',
                'name' => '💊 ضدالتهاب‌ها',
                'parent' => 'otc',
                'description' => 'داروهای ضد التهاب',
            ],
            [
                'slug' => 'antihistamine',
                'name' => '💊 آنتی‌هیستامین‌ها',
                'parent' => 'otc',
                'description' => 'داروهای ضد حساسیت',
            ],
            [
                'slug' => 'digestive',
                'name' => '💊 داروهای گوارشی',
                'parent' => 'otc',
                'description' => 'داروهای معده و گوارش',
            ],
            [
                'slug' => 'sedative',
                'name' => '💊 آرام‌بخش‌ها',
                'parent' => 'prescription',
                'description' => 'داروهای آرام‌بخش و ضد اضطراب',
            ],
            [
                'slug' => 'respiratory',
                'name' => '💊 داروهای تنفسی',
                'parent' => 'prescription',
                'description' => 'داروهای مشکلات تنفسی',
            ],
            [
                'slug' => 'laxative',
                'name' => '💊 ملین‌ها',
                'parent' => 'otc',
                'description' => 'داروهای ملین و رفع یبوست',
            ],
            [
                'slug' => 'parkinson',
                'name' => '💊 داروهای پارکینسون',
                'parent' => 'prescription',
                'description' => 'داروهای درمان پارکینسون',
            ],
            [
                'slug' => 'seizure',
                'name' => '💊 داروهای تشنج',
                'parent' => 'prescription',
                'description' => 'داروهای ضد تشنج',
            ],
            [
                'slug' => 'supplements',
                'name' => '🧪 مکمل‌ها',
                'parent' => 'otc',
                'description' => 'مکمل‌های غذایی و ویتامین‌ها',
            ],
        ];

        foreach ($subCategories as $sub) {
            $parent = $categories[$sub['parent']] ?? null;
            $category = ProductCategory::updateOrCreate(
                ['slug' => $sub['slug'], 'tenant_id' => $tenantId],
                [
                    'name' => $sub['name'],
                    'description' => $sub['description'],
                    'parent_id' => $parent?->id,
                    'is_active' => true,
                    'is_featured' => true,
                    'order' => 1,
                ]
            );
            $categories[$sub['slug']] = $category;
        }

        $this->command->info('🏷️  دسته‌بندی‌ها ایجاد شدند:');
        $this->command->info('   📁 داروها (والد)');
        $this->command->info('   ├── 📁 داروهای با نسخه');
        $this->command->info('   │   ├── 📁 آنتی‌بیوتیک‌ها');
        $this->command->info('   │   ├── 📁 داروهای فشار خون');
        $this->command->info('   │   ├── 📁 داروهای دیابت');
        $this->command->info('   │   ├── 📁 داروهای قلبی');
        $this->command->info('   │   ├── 📁 آرام‌بخش‌ها');
        $this->command->info('   │   ├── 📁 داروهای تنفسی');
        $this->command->info('   │   ├── 📁 داروهای پارکینسون');
        $this->command->info('   │   └── 📁 داروهای تشنج');
        $this->command->info('   └── 📁 داروهای آزاد (OTC)');
        $this->command->info('       ├── 📁 مسکن‌ها');
        $this->command->info('       ├── 📁 ضدالتهاب‌ها');
        $this->command->info('       ├── 📁 آنتی‌هیستامین‌ها');
        $this->command->info('       ├── 📁 داروهای گوارشی');
        $this->command->info('       ├── 📁 ملین‌ها');
        $this->command->info('       └── 📁 مکمل‌ها');

        return $categories;
    }

    private function generateCode(): string
    {
        $prefix = 'PRD';
        $year = now()->format('y');
        $month = now()->format('m');
        $random = str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        return "{$prefix}-{$year}{$month}-{$random}";
    }
}
