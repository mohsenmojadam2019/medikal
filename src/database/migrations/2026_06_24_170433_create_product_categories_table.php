<?php
// database/migrations/2026_07_29_000001_create_product_categories_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');

            // فیلدهای اصلی
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // ساختار درختی (Parent-Child)
            $table->foreignId('parent_id')->nullable()->constrained('product_categories')->onDelete('cascade');

            // فیلدهای مرتب‌سازی و نمایش
            $table->integer('order')->default(0);
            $table->string('color')->nullable();

            // فیلدهای وضعیت
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);

            // فیلدهای سئو
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // ایندکس‌ها
            $table->index(['tenant_id', 'parent_id']);
            $table->index(['is_active', 'is_featured']);
        });

        // جدول ارتباط محصولات با دسته‌بندی‌ها (Many-to-Many)
        Schema::create('category_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('product_categories')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['category_id', 'product_id']);
            $table->index(['product_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_product');
        Schema::dropIfExists('product_categories');
    }
};
