<?php

namespace Database\Seeders;

use App\Models\DigitalForm;
use App\Enums\FormStatusEnum;
use Illuminate\Database\Seeder;

class FormSeeder extends Seeder
{
    public function run(): void
    {
        // ============================================================
        // رضایت‌نامه درمانی
        // ============================================================

        DigitalForm::updateOrCreate(
            ['slug' => 'consent-form'],
            [
                'title' => 'رضایت‌نامه آگاهانه درمان',
                'slug' => 'consent-form',
                'description' => 'فرم رضایت‌نامه آگاهانه برای درمان و خدمات پزشکی',
                'category' => 'consent',
                'status' => FormStatusEnum::PUBLISHED,
                'is_active' => true,
                'fields' => [
                    [
                        'id' => 'full_name',
                        'label' => 'نام و نام خانوادگی',
                        'type' => 'text',
                        'required' => true,
                        'placeholder' => 'نام کامل خود را وارد کنید',
                    ],
                    [
                        'id' => 'national_code',
                        'label' => 'کد ملی',
                        'type' => 'text',
                        'required' => true,
                        'placeholder' => 'کد ملی خود را وارد کنید',
                    ],
                    [
                        'id' => 'phone',
                        'label' => 'شماره موبایل',
                        'type' => 'phone',
                        'required' => true,
                        'placeholder' => 'شماره موبایل خود را وارد کنید',
                    ],
                    [
                        'id' => 'treatment_description',
                        'label' => 'شرح درمان',
                        'type' => 'textarea',
                        'required' => true,
                        'placeholder' => 'شرح درمانی که قرار است انجام شود',
                    ],
                    [
                        'id' => 'risks',
                        'label' => 'آگاهی از خطرات',
                        'type' => 'checkbox',
                        'required' => true,
                        'options' => [
                            'من از خطرات و عوارض احتمالی درمان آگاه هستم',
                        ],
                    ],
                    [
                        'id' => 'consent',
                        'label' => 'رضایت',
                        'type' => 'checkbox',
                        'required' => true,
                        'options' => [
                            'من با انجام درمان موافقت می‌کنم',
                        ],
                    ],
                    [
                        'id' => 'signature',
                        'label' => 'امضای دیجیتال',
                        'type' => 'signature',
                        'required' => true,
                    ],
                ],
                'settings' => [
                    'allow_anonymous' => false,
                    'require_login' => false,
                    'show_progress' => true,
                    'confirmation_message' => 'با تشکر، رضایت‌نامه شما با موفقیت ثبت شد.',
                    'notify_admin' => true,
                    'notify_patient' => false,
                ],
                'created_by' => 1,
            ]
        );

        // ============================================================
        // فرم تاریخچه پزشکی
        // ============================================================

        DigitalForm::updateOrCreate(
            ['slug' => 'medical-history'],
            [
                'title' => 'فرم تاریخچه پزشکی',
                'slug' => 'medical-history',
                'description' => 'فرم ثبت تاریخچه پزشکی بیماران',
                'category' => 'medical_history',
                'status' => FormStatusEnum::PUBLISHED,
                'is_active' => true,
                'fields' => [
                    [
                        'id' => 'full_name',
                        'label' => 'نام و نام خانوادگی',
                        'type' => 'text',
                        'required' => true,
                        'placeholder' => 'نام کامل خود را وارد کنید',
                    ],
                    [
                        'id' => 'birth_date',
                        'label' => 'تاریخ تولد',
                        'type' => 'date',
                        'required' => true,
                    ],
                    [
                        'id' => 'gender',
                        'label' => 'جنسیت',
                        'type' => 'radio',
                        'required' => true,
                        'options' => ['مرد', 'زن'],
                    ],
                    [
                        'id' => 'blood_type',
                        'label' => 'گروه خونی',
                        'type' => 'select',
                        'required' => false,
                        'options' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'نامشخص'],
                    ],
                    [
                        'id' => 'allergies',
                        'label' => 'حساسیت‌ها',
                        'type' => 'textarea',
                        'required' => false,
                        'placeholder' => 'لطفاً حساسیت‌های دارویی و غذایی خود را وارد کنید',
                    ],
                    [
                        'id' => 'chronic_diseases',
                        'label' => 'بیماری‌های مزمن',
                        'type' => 'textarea',
                        'required' => false,
                        'placeholder' => 'بیماری‌های مزمن خود را وارد کنید (دیابت، فشار خون، و ...)',
                    ],
                    [
                        'id' => 'medications',
                        'label' => 'داروهای مصرفی',
                        'type' => 'textarea',
                        'required' => false,
                        'placeholder' => 'داروهای مصرفی خود را وارد کنید',
                    ],
                    [
                        'id' => 'family_history',
                        'label' => 'سابقه خانوادگی',
                        'type' => 'textarea',
                        'required' => false,
                        'placeholder' => 'سابقه بیماری در خانواده خود را وارد کنید',
                    ],
                    [
                        'id' => 'smoking',
                        'label' => 'استعمال دخانیات',
                        'type' => 'radio',
                        'required' => false,
                        'options' => ['هرگز', 'قبلاً مصرف می‌کردم', 'در حال مصرف'],
                    ],
                ],
                'settings' => [
                    'allow_anonymous' => false,
                    'require_login' => false,
                    'show_progress' => true,
                    'confirmation_message' => 'با تشکر، تاریخچه پزشکی شما با موفقیت ثبت شد.',
                    'notify_admin' => true,
                    'notify_patient' => false,
                ],
                'created_by' => 1,
            ]
        );

        // ============================================================
        // فرم بازخورد بیماران
        // ============================================================

        DigitalForm::updateOrCreate(
            ['slug' => 'patient-feedback'],
            [
                'title' => 'فرم بازخورد بیماران',
                'slug' => 'patient-feedback',
                'description' => 'فرم نظرسنجی و بازخورد بیماران از خدمات کلینیک',
                'category' => 'feedback',
                'status' => FormStatusEnum::PUBLISHED,
                'is_active' => true,
                'fields' => [
                    [
                        'id' => 'full_name',
                        'label' => 'نام و نام خانوادگی (اختیاری)',
                        'type' => 'text',
                        'required' => false,
                        'placeholder' => 'نام خود را وارد کنید (اختیاری)',
                    ],
                    [
                        'id' => 'doctor_name',
                        'label' => 'نام پزشک معالج',
                        'type' => 'text',
                        'required' => false,
                        'placeholder' => 'نام پزشک خود را وارد کنید',
                    ],
                    [
                        'id' => 'service_quality',
                        'label' => 'کیفیت خدمات',
                        'type' => 'radio',
                        'required' => true,
                        'options' => ['عالی', 'خوب', 'متوسط', 'ضعیف', 'خیلی ضعیف'],
                    ],
                    [
                        'id' => 'doctor_expertise',
                        'label' => 'تخصص و مهارت پزشک',
                        'type' => 'radio',
                        'required' => true,
                        'options' => ['عالی', 'خوب', 'متوسط', 'ضعیف', 'خیلی ضعیف'],
                    ],
                    [
                        'id' => 'waiting_time',
                        'label' => 'زمان انتظار',
                        'type' => 'radio',
                        'required' => true,
                        'options' => ['کمتر از ۱۰ دقیقه', '۱۰ تا ۲۰ دقیقه', '۲۰ تا ۳۰ دقیقه', 'بیش از ۳۰ دقیقه'],
                    ],
                    [
                        'id' => 'cleanliness',
                        'label' => 'نظافت و بهداشت',
                        'type' => 'radio',
                        'required' => true,
                        'options' => ['عالی', 'خوب', 'متوسط', 'ضعیف', 'خیلی ضعیف'],
                    ],
                    [
                        'id' => 'staff_behavior',
                        'label' => 'رفتار پرسنل',
                        'type' => 'radio',
                        'required' => true,
                        'options' => ['عالی', 'خوب', 'متوسط', 'ضعیف', 'خیلی ضعیف'],
                    ],
                    [
                        'id' => 'comment',
                        'label' => 'نظرات و پیشنهادات',
                        'type' => 'textarea',
                        'required' => false,
                        'placeholder' => 'نظرات و پیشنهادات خود را برای بهبود خدمات بنویسید',
                    ],
                    [
                        'id' => 'recommend',
                        'label' => 'آیا کلینیک را به دیگران توصیه می‌کنید؟',
                        'type' => 'radio',
                        'required' => true,
                        'options' => ['بله', 'خیر'],
                    ],
                ],
                'settings' => [
                    'allow_anonymous' => true,
                    'require_login' => false,
                    'show_progress' => true,
                    'confirmation_message' => 'با تشکر از بازخورد شما. نظرات شما به بهبود کیفیت خدمات ما کمک می‌کند.',
                    'notify_admin' => true,
                    'notify_patient' => false,
                ],
                'created_by' => 1,
            ]
        );

        $this->command->info('✅ فرم‌های دیجیتال ایجاد شدند.');
        $this->command->info('✅ ۳ فرم نمونه ایجاد شد:');
        $this->command->info('   1. رضایت‌نامه آگاهانه درمان');
        $this->command->info('   2. فرم تاریخچه پزشکی');
        $this->command->info('   3. فرم بازخورد بیماران');
    }
}
