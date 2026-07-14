<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_configs', function (Blueprint $table) {
            // فیلدهای اصلی
            $table->id();

            // کلید و مقدار
            $table->string('key', 100)->unique();
            $table->text('value');

            // نوع داده
            $table->enum('type', ['string', 'integer', 'boolean', 'json', 'array'])->default('string');

            // توضیحات
            $table->text('description')->nullable();

            // وضعیت
            $table->boolean('is_editable')->default(true);
            $table->string('category', 50)->default('general');

            // مقدار پیش‌فرض
            $table->text('default_value')->nullable();

            // زمان‌ها
            $table->timestamps();

            // ایندکس‌ها
            $table->index('category');
            $table->index('is_editable');
            $table->index(['category', 'is_editable']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_configs');
    }
};
