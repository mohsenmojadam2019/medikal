<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 شروع سیدر کامل...');

        // ============================================================
        // فقط اگه جدول خالی باشه، سیدر مربوطه اجرا میشه
        // ============================================================

        // ۱. استان‌ها
        if (Schema::hasTable('provinces') && \App\Models\Province::count() === 0) {
            $this->call(ProvinceSeeder::class);
        } else {
            $this->command->info('⏭️ استان‌ها قبلاً وجود دارند.');
        }

        // ۲. شهرها
        if (Schema::hasTable('cities') && \App\Models\City::count() === 0) {
            $this->call(CitySeeder::class);
        } else {
            $this->command->info('⏭️ شهرها قبلاً وجود دارند.');
        }

        // ۳. تخصص‌ها
        if (Schema::hasTable('specialties') && \App\Models\Specialty::count() === 0) {
            $this->call(SpecialtySeeder::class);
        } else {
            $this->command->info('⏭️ تخصص‌ها قبلاً وجود دارند.');
        }

        // ۴. نقش‌ها و مجوزها
        if (Schema::hasTable('roles') && \Spatie\Permission\Models\Role::count() === 0) {
            $this->call(RolePermissionSeeder::class);
        } else {
            $this->command->info('⏭️ نقش‌ها و مجوزها قبلاً وجود دارند.');
        }

        // ۵. کلینیک
        if (Schema::hasTable('clinics') && \App\Models\Clinic::count() === 0) {
            $this->call(ClinicSeeder::class);
        } else {
            $this->command->info('⏭️ کلینیک قبلاً وجود دارد.');
        }

        // ۶. بلاگ (دسته‌بندی‌ها و تگ‌ها)
        if (Schema::hasTable('post_categories') && \App\Models\PostCategory::count() === 0) {
            $this->call(BlogSeeder::class);
        } else {
            $this->command->info('⏭️ دسته‌بندی‌های بلاگ قبلاً وجود دارند.');
        }

        $this->command->info('✅ سیدر کامل با موفقیت انجام شد!');
    }
}
