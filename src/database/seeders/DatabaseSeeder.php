<?php
// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 شروع سیدر کامل...');

        // ============================================================
        // ۰. Tenant (مستاجر) - اولویت اول
        // ============================================================
        if (Schema::hasTable('tenants') && \App\Models\Tenant::count() === 0) {
            $this->call(TenantSeeder::class);
        } else {
            $this->command->info('⏭️ Tenant‌ها قبلاً وجود دارند.');
        }

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


        // ============================================================
        // ۱۰. زبان‌ها و ترجمه‌ها
        // ============================================================
        if (Schema::hasTable('languages') && \App\Models\Language::count() === 0) {
            $this->call(LanguageSeeder::class);
        } else {
            $this->command->info('⏭️ زبان‌ها قبلاً وجود دارند.');
        }

        // ============================================================
        // ۱۱. فرم‌های دیجیتال
        // ============================================================
        if (Schema::hasTable('digital_forms') && \App\Models\DigitalForm::count() === 0) {
            $this->call(FormSeeder::class);
        } else {
            $this->command->info('⏭️ فرم‌های دیجیتال قبلاً وجود دارند.');
        }

        // ============================================================
        // ۱۲. پلن‌های اشتراک
        // ============================================================
        if (Schema::hasTable('subscription_plans') && \App\Models\SubscriptionPlan::count() === 0) {
            $this->call(SubscriptionPlanSeeder::class);
        } else {
            $this->command->info('⏭️ پلن‌های اشتراک قبلاً وجود دارند.');
        }

        // ============================================================
        // ۱۳. بخش‌های بیمارستان
        // ============================================================
//        if (Schema::hasTable('wards') && \App\Models\Ward::count() === 0) {
//            $this->call(HospitalSeeder::class);
//        } else {
//            $this->command->info('⏭️ بخش‌های بیمارستان قبلاً وجود دارند.');
//        }

        // ============================================================
        // ۱۴. نوبت‌های تست (اختیاری)
        // ============================================================
        // اگر می‌خواهید نوبت‌های تست ایجاد شود، این بخش را فعال کنید
        // if (\App\Models\Appointment::count() === 0) {
        //     $this->call(DoctorAppointmentSeeder::class);
        // } else {
        //     $this->command->info('⏭️ نوبت‌های تست قبلاً وجود دارند.');
        // }

        // ============================================================
        // ۱۵. گزارش‌های BI (اختیاری)
        // ============================================================
//        if (Schema::hasTable('bi_reports') && \App\Models\BI\BIReport::count() === 0) {
//            $this->call(\Database\Seeders\BI\BISeeder::class);
//        } else {
//            $this->command->info('⏭️ گزارش‌های BI قبلاً وجود دارند.');
//        }

        $this->command->info('');
        $this->command->info('✅ سیدر کامل با موفقیت انجام شد!');
        $this->command->info('📊 خلاصه داده‌های ایجاد شده:');
        $this->command->info('   🏢 Tenant‌ها: ' . \App\Models\Tenant::count());
        $this->command->info('   🏥 کلینیک‌ها: ' . \App\Models\Clinic::count());
        $this->command->info('   👨‍⚕️ پزشکان: ' . \App\Models\Doctor::count());
        $this->command->info('   👤 بیماران: ' . \App\Models\Patient::count());
        $this->command->info('   💊 داروخانه‌ها: ' . \App\Models\Pharmacy::count());
        $this->command->info('   💊 داروها: ' . \App\Models\Drug::count());
        $this->command->info('   📷 مراکز تصویربرداری: ' . \App\Models\PACS\MedicalImage::count());
        $this->command->info('   🧪 آزمایشگاه‌ها: ' . \App\Models\LabTest::count());
        $this->command->info('   🌐 زبان‌ها: ' . \App\Models\Language::count());
        $this->command->info('   📋 فرم‌های دیجیتال: ' . \App\Models\DigitalForm::count());
        $this->command->info('   💳 پلن‌های اشتراک: ' . \App\Models\SubscriptionPlan::count());
        $this->command->info('');
        $this->command->info('🔑 اطلاعات ورود ادمین:');
        $this->command->info('   📱 موبایل: 09123456789');
        $this->command->info('   🔑 رمز عبور: 12345678');
        $this->command->info('');
        $this->command->info('🎉 همه چیز با موفقیت انجام شد!');
    }
}
