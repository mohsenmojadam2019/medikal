<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            // فیلدهای اصلی
            $table->id();
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('user_id');

            // نقش و محتوا
            $table->enum('role', ['user', 'assistant', 'system']);
            $table->text('content');

            // اطلاعات مدل و توکن
            $table->string('model_used', 50)->nullable();
            $table->integer('tokens_used')->nullable();
            $table->integer('response_time')->nullable()->comment('زمان پاسخ‌دهی به میلی‌ثانیه');

            // وضعیت‌ها
            $table->boolean('is_emergency')->default(false);
            $table->boolean('is_medical')->default(true);
            $table->string('category', 50)->nullable();
            $table->float('confidence_score', 5, 2)->nullable();
            $table->enum('severity', ['normal', 'urgent', 'emergency'])->default('normal');

            // اطلاعات اضافی
            $table->json('metadata')->nullable();

            // زمان‌ها
            $table->timestamps();

            // ایندکس‌ها
            $table->index('session_id');
            $table->index('user_id');
            $table->index('role');
            $table->index('is_emergency');
            $table->index('created_at');
            $table->index(['session_id', 'created_at']);

            // کلیدهای خارجی
            $table->foreign('session_id')
                ->references('id')
                ->on('chat_sessions')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
