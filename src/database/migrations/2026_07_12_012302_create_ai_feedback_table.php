<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_feedback', function (Blueprint $table) {
            // فیلدهای اصلی
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('message_id');
            $table->unsignedBigInteger('session_id')->nullable();

            // امتیاز و نظر
            $table->tinyInteger('rating')->comment('امتیاز ۱ تا ۵');
            $table->text('comment')->nullable();
            $table->boolean('is_helpful')->default(true);

            // اطلاعات اضافی
            $table->json('metadata')->nullable();

            // زمان‌ها
            $table->timestamps();

            // ایندکس‌ها
            $table->index('user_id');
            $table->index('message_id');
            $table->index('session_id');
            $table->index('is_helpful');
            $table->index('created_at');
            $table->index(['user_id', 'created_at']);
            $table->index(['message_id', 'is_helpful']);

            // کلیدهای خارجی
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('message_id')
                ->references('id')
                ->on('chat_messages')
                ->onDelete('cascade');

            $table->foreign('session_id')
                ->references('id')
                ->on('chat_sessions')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_feedback');
    }
};
