<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_sessions', function (Blueprint $table) {
            // فیلدهای اصلی
            $table->id();
            $table->unsignedBigInteger('user_id');

            // فیلدهای جلسه
            $table->string('session_token', 100)->unique();
            $table->string('title', 255)->nullable();
            $table->enum('status', ['active', 'expired', 'closed', 'deleted'])->default('active');

            // اطلاعات مدل و دسته‌بندی
            $table->string('model_used', 50)->nullable();
            $table->string('category', 50)->nullable();

            // زمان‌ها
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_activity')->nullable();

            // آمار
            $table->integer('message_count')->default(0);

            // اطلاعات اضافی
            $table->json('metadata')->nullable();

            // زمان‌های سیستمی
            $table->timestamps();
            $table->softDeletes();

            // ایندکس‌ها
            $table->index('user_id');
            $table->index('status');
            $table->index('expires_at');
            $table->index('created_at');

            // کلید خارجی
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_sessions');
    }
};
