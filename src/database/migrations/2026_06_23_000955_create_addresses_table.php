<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();

            // Polymorphic (خودش ایندکس می‌سازه)
            $table->morphs('addressable');

            // Location
            $table->foreignId('province_id')->constrained('provinces');
            $table->foreignId('city_id')->constrained('cities');

            // Address details
            $table->string('address_line_1', 500);
            $table->string('address_line_2', 500)->nullable();
            $table->string('neighborhood', 100)->nullable();
            $table->string('street', 200)->nullable();
            $table->string('alley', 100)->nullable();
            $table->string('plaque', 50)->nullable();
            $table->string('unit', 50)->nullable();

            // Contact
            $table->string('postal_code', 20)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('mobile', 20)->nullable();

            // Coordinates
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Settings
            $table->string('type', 20)->default('home');
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // Indexes (فقط ایندکس‌های اضافی، بدون duplicate)
            $table->index(['province_id', 'city_id']);
            $table->index('is_primary');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
