<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cleanup_logs', function (Blueprint $table) {
            // فیلدهای اصلی
            $table->id();

            // اطلاعات پاکسازی
            $table->string('table_name', 100);
            $table->integer('deleted_count')->default(0);
            $table->timestamp('deleted_before')->nullable();

            // نحوه اجرا
            $table->enum('triggered_by', ['system', 'manual'])->default('system');
            $table->unsignedBigInteger('triggered_by_user')->nullable();

            // وضعیت
            $table->enum('status', ['success', 'failed'])->default('success');
            $table->text('error_message')->nullable();

            // اطلاعات اضافی
            $table->json('metadata')->nullable();

            // زمان
            $table->timestamp('created_at')->useCurrent();

            // ایندکس‌ها
            $table->index('table_name');
            $table->index('triggered_by');
            $table->index('status');
            $table->index('created_at');
            $table->index(['table_name', 'created_at']);
            $table->index(['status', 'created_at']);

            // کلید خارجی
            $table->foreign('triggered_by_user')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cleanup_logs');
    }
};
