<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_files', function (Blueprint $table) {
            // فیلدهای اصلی
            $table->id();
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('message_id')->nullable();

            // اطلاعات فایل
            $table->string('original_name', 255);
            $table->string('file_name', 255);
            $table->integer('file_size')->comment('حجم بر حسب کیلوبایت');
            $table->string('mime_type', 100);
            $table->enum('file_type', ['image', 'pdf', 'document', 'other'])->default('other');

            // وضعیت و انقضا
            $table->timestamp('expires_at')->nullable();

            // زمان‌ها
            $table->timestamps();
            $table->softDeletes();

            // ایندکس‌ها
            $table->index('session_id');
            $table->index('user_id');
            $table->index('message_id');
            $table->index('file_type');
            $table->index('expires_at');

            // کلیدهای خارجی
            $table->foreign('session_id')
                ->references('id')
                ->on('chat_sessions')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('message_id')
                ->references('id')
                ->on('chat_messages')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_files');
    }
};
