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
        // ۱. داده‌های پایه
        // ============================================================
        if (Schema::hasTable('provinces') && \App\Models\Province::count() === 0) {
            $this->call(ProvinceSeeder::class);
        } else {
            $this->command->info('⏭️ استان‌ها قبلاً وجود دارند.');
        }

        if (Schema::hasTable('cities') && \App\Models\City::count() === 0) {
            $this->call(CitySeeder::class);
        } else {
            $this->command->info('⏭️ شهرها قبلاً وجود دارند.');
        }

        if (Schema::hasTable('specialties') && \App\Models\Specialty::count() === 0) {
            $this->call(SpecialtySeeder::class);
        } else {
            $this->command->info('⏭️ تخصص‌ها قبلاً وجود دارند.');
        }

        if (Schema::hasTable('roles') && \Spatie\Permission\Models\Role::count() === 0) {
            $this->call(RolePermissionSeeder::class);
        } else {
            $this->command->info('⏭️ نقش‌ها و مجوزها قبلاً وجود دارند.');
        }

        // ============================================================
        // ۲. کلینیک‌ها
        // ============================================================
        if (Schema::hasTable('clinics') && \App\Models\Clinic::count() === 0) {
            $this->call(ClinicSeeder::class);
        } else {
            $this->command->info('⏭️ کلینیک‌ها قبلاً وجود دارند.');
        }

        // ============================================================
        // ۳. پزشکان
        // ============================================================
        if (Schema::hasTable('doctors') && \App\Models\Doctor::count() === 0) {
            $this->call(DoctorsSeeder::class);
        } else {
            $this->command->info('⏭️ پزشکان قبلاً وجود دارند.');
        }

        // ============================================================
        // ۴. داروخانه‌ها
        // ============================================================
        if (Schema::hasTable('pharmacies') && \App\Models\Pharmacy::count() === 0) {
            $this->call(PharmaciesSeeder::class);
        } else {
            $this->command->info('⏭️ داروخانه‌ها قبلاً وجود دارند.');
        }

        // ============================================================
        // ۵. تصویربرداری
        // ============================================================
        if (Schema::hasTable('medical_images') && \App\Models\PACS\MedicalImage::count() === 0) {
            $this->call(ImagingCentersSeeder::class);
        } else {
            $this->command->info('⏭️ مراکز تصویربرداری قبلاً وجود دارند.');
        }

        // ============================================================
        // ۶. بلاگ
        // ============================================================
        if (Schema::hasTable('post_categories') && \App\Models\PostCategory::count() === 0) {
            $this->call(BlogSeeder::class);
        } else {
            $this->command->info('⏭️ دسته‌بندی‌های بلاگ قبلاً وجود دارند.');
        }

        // ============================================================
        // ۷. داروها
        // ============================================================
        if (Schema::hasTable('drugs') && \App\Models\Drug::count() === 0) {
            $this->call(DrugsSeeder::class);
        } else {
            $this->command->info('⏭️ داروها قبلاً وجود دارند.');
        }

        // ============================================================
        // ۸. آزمایشگاه
        // ============================================================
        if (Schema::hasTable('lab_tests') && \App\Models\LabTest::count() === 0) {
            $this->call(LabTestsSeeder::class);
        } else {
            $this->command->info('⏭️ آزمایشگاه‌ها قبلاً وجود دارند.');
        }


// ============================================================
// ۹. اورژانس
// ============================================================
        if (Schema::hasTable('emergency_patients') && \App\Models\Emergency\EmergencyPatient::count() === 0) {
            $this->call(EmergencySeeder::class);
        } else {
            $this->command->info('⏭️ درخواست‌های اورژانس قبلاً وجود دارند.');
        }


        $this->command->info('✅ سیدر کامل با موفقیت انجام شد!');
        $this->command->info('📊 خلاصه:');
        $this->command->info('   🏥 کلینیک‌ها: ' . \App\Models\Clinic::count());
        $this->command->info('   👨‍⚕️ پزشکان: ' . \App\Models\Doctor::count());
        $this->command->info('   💊 داروخانه‌ها: ' . \App\Models\Pharmacy::count());
        $this->command->info('   📷 مراکز تصویربرداری: ' . \App\Models\PACS\MedicalImage::count());
        $this->command->info('   🧪 آزمایشگاه‌ها: ' . \App\Models\LabTest::count());
        $this->command->info('   💊 داروها: ' . \App\Models\Drug::count());
    }
}
