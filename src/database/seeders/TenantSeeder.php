<?php
// database/seeders/TenantSeeder.php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        // ابتدا بررسی کن که آیا Tenant وجود دارد یا خیر
        if (Tenant::count() > 0) {
            $this->command->info('ℹ️ Tenant‌ها قبلاً در دیتابیس وجود دارند.');
            $this->command->info('📊 تعداد Tenant‌ها: ' . Tenant::count());
            return;
        }

        $tenants = [
            [
                'name' => 'کلینیک دکتر وب',
                'slug' => 'dr-web',
                'domain' => null,
                'subdomain' => 'drweb',
                'email' => 'info@drweb.com',
                'phone' => '021-22222222',
                'address' => 'تهران، خیابان ولیعصر، پلاک ۱۲۳',
                'logo' => null,
                'subscription_status' => 'active',
                'subscription_expires_at' => now()->addYear(),
                'trial_ends_at' => null,
                'max_doctors' => 20,
                'max_patients' => 1000,
                'max_appointments_per_day' => 100,
                'max_prescriptions_per_month' => 500,
                'features' => json_encode([
                    'telemedicine' => true,
                    'pharmacy' => true,
                    'lab' => true,
                    'pacs' => true,
                    'blog' => true,
                    'ai_chat' => true,
                    'insurance' => true,
                    'installment' => true,
                    'reports' => true,
                    'api_access' => true,
                ]),
                'settings' => json_encode([
                    'appointment_reminder_hours' => 24,
                    'enable_online_payment' => true,
                    'enable_telemedicine' => true,
                    'default_language' => 'fa',
                    'timezone' => 'Asia/Tehran',
                ]),
                'is_active' => true,
                'is_verified' => true,
                'created_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'کلینیک سلامت پارس',
                'slug' => 'salamat-pars',
                'domain' => null,
                'subdomain' => 'salamat',
                'email' => 'info@salamatpars.com',
                'phone' => '021-33333333',
                'address' => 'تهران، خیابان انقلاب، پلاک ۴۵',
                'logo' => null,
                'subscription_status' => 'active',
                'subscription_expires_at' => now()->addYear(),
                'trial_ends_at' => null,
                'max_doctors' => 15,
                'max_patients' => 700,
                'max_appointments_per_day' => 70,
                'max_prescriptions_per_month' => 300,
                'features' => json_encode([
                    'telemedicine' => true,
                    'pharmacy' => true,
                    'lab' => true,
                    'pacs' => false,
                    'blog' => true,
                    'ai_chat' => true,
                    'insurance' => true,
                    'installment' => false,
                    'reports' => true,
                    'api_access' => true,
                ]),
                'settings' => json_encode([
                    'appointment_reminder_hours' => 24,
                    'enable_online_payment' => true,
                    'enable_telemedicine' => true,
                    'default_language' => 'fa',
                    'timezone' => 'Asia/Tehran',
                ]),
                'is_active' => true,
                'is_verified' => true,
                'created_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'کلینیک مهرگان',
                'slug' => 'mehragan',
                'domain' => null,
                'subdomain' => 'mehragan',
                'email' => 'info@mehragan.com',
                'phone' => '031-44444444',
                'address' => 'اصفهان، خیابان چهارباغ، پلاک ۷۸',
                'logo' => null,
                'subscription_status' => 'active',
                'subscription_expires_at' => now()->addYear(),
                'trial_ends_at' => null,
                'max_doctors' => 10,
                'max_patients' => 500,
                'max_appointments_per_day' => 50,
                'max_prescriptions_per_month' => 200,
                'features' => json_encode([
                    'telemedicine' => true,
                    'pharmacy' => true,
                    'lab' => true,
                    'pacs' => false,
                    'blog' => false,
                    'ai_chat' => true,
                    'insurance' => false,
                    'installment' => false,
                    'reports' => true,
                    'api_access' => false,
                ]),
                'settings' => json_encode([
                    'appointment_reminder_hours' => 24,
                    'enable_online_payment' => true,
                    'enable_telemedicine' => true,
                    'default_language' => 'fa',
                    'timezone' => 'Asia/Tehran',
                ]),
                'is_active' => true,
                'is_verified' => true,
                'created_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'کلینیک امید',
                'slug' => 'omid',
                'domain' => null,
                'subdomain' => 'omid',
                'email' => 'info@omid.com',
                'phone' => '071-55555555',
                'address' => 'شیراز، خیابان زند، پلاک ۵۶',
                'logo' => null,
                'subscription_status' => 'trial',
                'subscription_expires_at' => now()->addDays(30),
                'trial_ends_at' => now()->addDays(30),
                'max_doctors' => 5,
                'max_patients' => 200,
                'max_appointments_per_day' => 20,
                'max_prescriptions_per_month' => 50,
                'features' => json_encode([
                    'telemedicine' => true,
                    'pharmacy' => true,
                    'lab' => false,
                    'pacs' => false,
                    'blog' => false,
                    'ai_chat' => false,
                    'insurance' => false,
                    'installment' => false,
                    'reports' => true,
                    'api_access' => false,
                ]),
                'settings' => json_encode([
                    'appointment_reminder_hours' => 24,
                    'enable_online_payment' => true,
                    'enable_telemedicine' => true,
                    'default_language' => 'fa',
                    'timezone' => 'Asia/Tehran',
                ]),
                'is_active' => true,
                'is_verified' => false,
                'created_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($tenants as $data) {
            $tenant = Tenant::create($data);
            $this->command->info("✅ Tenant ایجاد شد: {$tenant->name} (ID: {$tenant->id})");
        }

        $this->command->info('✅ ' . count($tenants) . ' Tenant با موفقیت ایجاد شدند.');
        $this->command->info('📊 خلاصه:');
        $this->command->info('   🏥 دکتر وب: active (20 پزشک، 1000 بیمار)');
        $this->command->info('   🏥 سلامت پارس: active (15 پزشک، 700 بیمار)');
        $this->command->info('   🏥 مهرگان: active (10 پزشک، 500 بیمار)');
        $this->command->info('   🏥 امید: trial (5 پزشک، 200 بیمار)');
    }
}
