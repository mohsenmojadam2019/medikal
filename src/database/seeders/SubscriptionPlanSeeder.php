<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'طلایی (رایگان)',
                'slug' => 'gold-free',
                'description' => 'پلن رایگان برای شروع کار',
                'icon' => 'fa-crown',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'is_free' => true,
                'max_doctors' => 2,
                'max_patients' => 50,
                'max_appointments_per_day' => 10,
                'max_prescriptions_per_month' => 5,
                'features' => [
                    'basic_appointments' => true,
                    'basic_patients' => true,
                    'basic_reports' => true,
                    'sms_reminder' => true,
                ],
                'is_default' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'نقره‌ای (پایه)',
                'slug' => 'silver-basic',
                'description' => 'پلن مناسب برای کلینیک‌های کوچک',
                'icon' => 'fa-gem',
                'price_monthly' => 299000,
                'price_yearly' => 2990000,
                'is_free' => false,
                'max_doctors' => 5,
                'max_patients' => 200,
                'max_appointments_per_day' => 30,
                'max_prescriptions_per_month' => -1,
                'features' => [
                    'basic_appointments' => true,
                    'basic_patients' => true,
                    'advanced_reports' => true,
                    'sms_reminder' => true,
                    'email_reminder' => true,
                    'export_excel' => true,
                    'export_pdf' => true,
                    'no_branding' => true,
                ],
                'is_default' => false,
                'sort_order' => 2,
            ],
            [
                'name' => 'طلایی (حرفه‌ای)',
                'slug' => 'gold-professional',
                'description' => 'پلن کامل برای کلینیک‌های متوسط',
                'icon' => 'fa-star',
                'price_monthly' => 599000,
                'price_yearly' => 5990000,
                'is_free' => false,
                'max_doctors' => 15,
                'max_patients' => 500,
                'max_appointments_per_day' => 100,
                'max_prescriptions_per_month' => -1,
                'features' => [
                    'basic_appointments' => true,
                    'basic_patients' => true,
                    'advanced_reports' => true,
                    'sms_reminder' => true,
                    'email_reminder' => true,
                    'export_excel' => true,
                    'export_pdf' => true,
                    'no_branding' => true,
                    'telemedicine' => true,
                    'insurance_integration' => true,
                    'referral_system' => true,
                    'api_access' => true,
                ],
                'is_default' => false,
                'sort_order' => 3,
            ],
            [
                'name' => 'الماس (سازمانی)',
                'slug' => 'diamond-enterprise',
                'description' => 'پلن ویژه کلینیک‌های بزرگ',
                'icon' => 'fa-crown',
                'price_monthly' => 1199000,
                'price_yearly' => 11990000,
                'is_free' => false,
                'max_doctors' => -1,
                'max_patients' => -1,
                'max_appointments_per_day' => -1,
                'max_prescriptions_per_month' => -1,
                'features' => [
                    'basic_appointments' => true,
                    'basic_patients' => true,
                    'advanced_reports' => true,
                    'sms_reminder' => true,
                    'email_reminder' => true,
                    'export_excel' => true,
                    'export_pdf' => true,
                    'no_branding' => true,
                    'telemedicine' => true,
                    'insurance_integration' => true,
                    'referral_system' => true,
                    'api_access' => true,
                    'dedicated_server' => true,
                    'custom_domain' => true,
                    'white_label' => true,
                    'custom_reports' => true,
                    'priority_support' => true,
                    'free_training' => true,
                    'money_back_guarantee' => true,
                ],
                'is_default' => false,
                'sort_order' => 4,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }

        $this->command->info('✅ پلن‌های اشتراک با موفقیت ایجاد شدند.');
    }
}
