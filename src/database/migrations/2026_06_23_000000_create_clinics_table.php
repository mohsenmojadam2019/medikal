<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('clinics')) {
            Schema::create('clinics', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique()->nullable();
                $table->foreignId('province_id')->nullable()->constrained('provinces')->nullOnDelete();
                $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
                $table->text('address')->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->string('website')->nullable();
                $table->string('logo')->nullable();
                $table->string('favicon')->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->string('timezone')->default('Asia/Tehran');
                $table->string('currency')->default('تومان');
                $table->string('language')->default('fa');
                $table->decimal('tax_rate', 5, 2)->default(9);
                $table->string('invoice_prefix')->default('INV');
                $table->string('appointment_prefix')->default('APP');
                $table->string('primary_color')->default('#2b6cb0');
                $table->string('secondary_color')->default('#ed8936');
                $table->string('theme')->default('default');
                $table->boolean('is_active')->default(true);
                $table->boolean('is_verified')->default(false);
                $table->json('metadata')->nullable();
                $table->json('settings')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });

            // فقط با echo کار کن (بدون command)
            echo "✅ جدول clinics ایجاد شد.\n";
        } else {
            echo "ℹ️ جدول clinics قبلاً وجود دارد.\n";
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('clinics');
    }
};
