<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_prompts', function (Blueprint $table) {
            // فیلدهای اصلی
            $table->id();

            // اطلاعات پرامپت
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->enum('category', [
                'general', 'medical', 'pharmacy',
                'emergency', 'nutrition', 'psychology'
            ])->default('general');

            // محتوای پرامپت
            $table->text('system_prompt');
            $table->text('user_prompt_template');

            // نسخه‌بندی
            $table->integer('version')->default(1);

            // وضعیت
            $table->boolean('is_active')->default(false);
            $table->boolean('is_default')->default(false);
            $table->integer('priority')->default(0);

            // تنظیمات خاص
            $table->json('config')->nullable();

            // آمار استفاده
            $table->integer('usage_count')->default(0);

            // اطلاعات ایجاد
            $table->unsignedBigInteger('created_by')->nullable();

            // زمان‌ها
            $table->timestamps();

            // ایندکس‌ها
            $table->index('category');
            $table->index('is_active');
            $table->index('is_default');
            $table->index('created_by');
            $table->index(['category', 'is_active']);

            // کلید خارجی
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_prompts');
    }
};
