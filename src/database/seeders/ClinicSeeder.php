<?php

namespace Database\Seeders;

use App\Models\Clinic;
use Illuminate\Database\Seeder;

class ClinicSeeder extends Seeder
{
    public function run(): void
    {
        if (Clinic::count() === 0) {
            Clinic::create([
                'name' => 'کلینیک نمونه',
                'slug' => 'clinic-sample',
                'address' => 'تهران، خیابان ولیعصر، پلاک ۱۲۳',
                'phone' => '۰۲۱-۲۲۲۲۲۲۲۲',
                'email' => 'info@clinic.com',
                'website' => 'https://clinic.com',
                'timezone' => 'Asia/Tehran',
                'currency' => 'تومان',
                'language' => 'fa',
                'tax_rate' => 9,
                'invoice_prefix' => 'INV',
                'appointment_prefix' => 'APP',
                'primary_color' => '#2b6cb0',
                'secondary_color' => '#ed8936',
                'is_active' => true,
                'is_verified' => true,
            ]);

            $this->command->info('✅ کلینیک پیش‌فرض ایجاد شد.');
        } else {
            $this->command->info('ℹ️ کلینیک قبلاً وجود دارد.');
        }
    }
}
