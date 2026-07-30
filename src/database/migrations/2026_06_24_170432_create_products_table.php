<?php
// database/migrations/2026_07_30_000001_create_products_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pharmacy_id')->nullable()->constrained('pharmacies')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();

            // اطلاعات پایه
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('generic_name')->nullable();
            $table->string('code')->unique();
            $table->string('category')->nullable();

            // مشخصات محصول
            $table->string('form')->nullable(); // قرص، کپسول، ژل، شربت، ...
            $table->string('strength')->nullable(); // ۵۰۰ میلی‌گرم، ۱۰۰ میکروگرم، ...
            $table->string('manufacturer')->nullable();
            $table->string('country_of_origin')->nullable();
            $table->string('license_from')->nullable();

            // ویژگی‌های فیزیکی
            $table->string('product_form')->nullable(); // ژل، کرم، موس، ...
            $table->string('volume')->nullable(); // ۱۵۰ میلی‌لیتر
            $table->string('container_type')->nullable(); // تیوب، بطری، ...
            $table->string('container_material')->nullable(); // پلاستیکی، شیشه‌ای
            $table->string('weight')->nullable(); // ۱۷۵ گرم
            $table->string('dimensions')->nullable(); // ۶ × ۲ × ۱۵ سانتیمتر

            // قیمت و موجودی
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discounted_price', 15, 2)->nullable();
            $table->boolean('has_discount')->default(false);
            $table->integer('stock')->default(0);

            // نسخه و وضعیت
            $table->boolean('requires_prescription')->default(true);
            $table->boolean('is_active')->default(true);

            // توضیحات و ویژگی‌ها
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->json('product_features')->nullable(); // آرایه ویژگی‌ها
            $table->json('usage_instructions')->nullable();

            // برچسب‌ها (tags) - ذخیره به صورت JSON (برای جستجوی سریع)
            $table->json('tags')->nullable();

            // امتیاز و بازخورد
            $table->float('avg_rating')->default(0);
            $table->integer('review_count')->default(0);
            $table->boolean('allow_reviews')->default(true);

            // متادیتا
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // ایندکس‌ها
            $table->index(['tenant_id', 'pharmacy_id']);
            $table->index(['category', 'is_active']);
            $table->index(['brand_id', 'is_active']);
            $table->index(['requires_prescription', 'is_active']);
            $table->index(['price', 'stock']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
