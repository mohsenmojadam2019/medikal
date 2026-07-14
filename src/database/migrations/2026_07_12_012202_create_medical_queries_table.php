<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_queries', function (Blueprint $table) {
            // فیلدهای اصلی
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('session_id')->nullable();

            // محتوا
            $table->text('question');
            $table->text('response')->nullable();

            // دسته‌بندی و شدت
            $table->string('category', 50)->nullable();
            $table->enum('severity', ['normal', 'urgent', 'emergency'])->default('normal');

            // اطلاعات تشخیصی
            $table->json('detected_symptoms')->nullable();
            $table->json('suggested_actions')->nullable();

            // وضعیت مدیریت
            $table->boolean('is_handled')->default(false);
            $table->unsignedBigInteger('handled_by')->nullable();
            $table->timestamp('handled_at')->nullable();

            // امتیاز هوش مصنوعی
            $table->float('ai_confidence', 5, 2)->nullable();

            // اطلاعات اضافی
            $table->json('metadata')->nullable();

            // زمان‌ها
            $table->timestamps();

            // ایندکس‌ها
            $table->index('user_id');
            $table->index('session_id');
            $table->index('category');
            $table->index('severity');
            $table->index('is_handled');
            $table->index('created_at');
            $table->index(['user_id', 'created_at']);
            $table->index(['category', 'severity']);

            // کلیدهای خارجی
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('session_id')
                ->references('id')
                ->on('chat_sessions')
                ->onDelete('set null');

            $table->foreign('handled_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_queries');
    }
};
