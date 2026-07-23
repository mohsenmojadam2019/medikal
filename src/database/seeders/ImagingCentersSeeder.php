<?php
// database/seeders/ImagingCentersSeeder.php

namespace Database\Seeders;

use App\Models\PACS\MedicalImage;
use App\Models\Clinic;
use App\Models\Province;
use App\Models\City;
use Illuminate\Database\Seeder;

class ImagingCentersSeeder extends Seeder
{
    public function run(): void
    {
        $clinics = Clinic::all()->keyBy('slug');

        $tehran = Province::where('name', 'تهران')->first();
        $tehranCity = City::where('name', 'تهران')->where('province_id', $tehran?->id)->first();

        $isfahan = Province::where('name', 'اصفهان')->first();
        $isfahanCity = City::where('name', 'اصفهان')->where('province_id', $isfahan?->id)->first();

        $shiraz = Province::where('name', 'فارس')->first();
        $shirazCity = City::where('name', 'شیراز')->where('province_id', $shiraz?->id)->first();

        $mashhad = Province::where('name', 'خراسان رضوی')->first();
        $mashhadCity = City::where('name', 'مشهد')->where('province_id', $mashhad?->id)->first();

        $tabriz = Province::where('name', 'آذربایجان شرقی')->first();
        $tabrizCity = City::where('name', 'تبریز')->where('province_id', $tabriz?->id)->first();

        $imagingCenters = [
            // ============================================================
            // ✅ مراکز تصویربرداری کلینیک دکتر وب (۲ مرکز)
            // ============================================================
            [
                'patient_id' => null,
                'doctor_id' => null,
                'clinic_id' => $clinics['dr-web']?->id,
                'province_id' => $tehran?->id,
                'city_id' => $tehranCity?->id,
                'admission_id' => null,
                'appointment_id' => null,
                'image_type' => 'mri',
                'file_name' => 'mri-machine-01.jpg',
                'file_path' => 'imaging/dr-web/mri-01.jpg',
                'file_size' => 2048576,
                'mime_type' => 'image/jpeg',
                'study_uid' => '1.2.840.113619.2.55.1.1.1.' . rand(100000, 999999),
                'series_uid' => '1.2.840.113619.2.55.1.1.2.' . rand(100000, 999999),
                'instance_uid' => '1.2.840.113619.2.55.1.1.3.' . rand(100000, 999999),
                'body_part' => 'brain',
                'modality' => 'MR',
                'description' => 'دستگاه MRI 3 تسلا - مرکز تصویربرداری دکتر وب',
                'study_date' => now()->subDays(rand(1, 30)),
                'report' => 'تصویر MRI مغز با کیفیت بالا برای تشخیص دقیق',
                'is_confidential' => false,
                'uploaded_by' => null,
                'metadata' => [
                    'center_name' => 'مرکز تصویربرداری دکتر وب',
                    'equipment' => 'Siemens Magnetom Vida 3T',
                    'services' => ['MRI', 'fMRI', 'MRA', 'DTI'],
                    'is_active' => true,
                ],
            ],
            [
                'patient_id' => null,
                'doctor_id' => null,
                'clinic_id' => $clinics['dr-web']?->id,
                'province_id' => $tehran?->id,
                'city_id' => $tehranCity?->id,
                'admission_id' => null,
                'appointment_id' => null,
                'image_type' => 'ct',
                'file_name' => 'ct-scan-01.jpg',
                'file_path' => 'imaging/dr-web/ct-01.jpg',
                'file_size' => 1536000,
                'mime_type' => 'image/jpeg',
                'study_uid' => '1.2.840.113619.2.55.1.2.1.' . rand(100000, 999999),
                'series_uid' => '1.2.840.113619.2.55.1.2.2.' . rand(100000, 999999),
                'instance_uid' => '1.2.840.113619.2.55.1.2.3.' . rand(100000, 999999),
                'body_part' => 'chest',
                'modality' => 'CT',
                'description' => 'دستگاه CT اسکن 128 اسلایس - مرکز تصویربرداری دکتر وب',
                'study_date' => now()->subDays(rand(1, 30)),
                'report' => 'سی‌تی اسکن قفسه سینه با کیفیت بالا',
                'is_confidential' => false,
                'uploaded_by' => null,
                'metadata' => [
                    'center_name' => 'مرکز تصویربرداری دکتر وب',
                    'equipment' => 'Siemens Somatom Force',
                    'services' => ['CT Scan', 'CT Angiography', 'CT Perfusion'],
                    'is_active' => true,
                ],
            ],

            // ============================================================
            // ✅ مرکز تصویربرداری کلینیک سلامت پارس
            // ============================================================
            [
                'patient_id' => null,
                'doctor_id' => null,
                'clinic_id' => $clinics['salamat-pars']?->id,
                'province_id' => $tehran?->id,
                'city_id' => $tehranCity?->id,
                'admission_id' => null,
                'appointment_id' => null,
                'image_type' => 'ultrasound',
                'file_name' => 'ultrasound-01.jpg',
                'file_path' => 'imaging/salamat-pars/us-01.jpg',
                'file_size' => 1024000,
                'mime_type' => 'image/jpeg',
                'study_uid' => '1.2.840.113619.2.55.1.3.1.' . rand(100000, 999999),
                'series_uid' => '1.2.840.113619.2.55.1.3.2.' . rand(100000, 999999),
                'instance_uid' => '1.2.840.113619.2.55.1.3.3.' . rand(100000, 999999),
                'body_part' => 'abdomen',
                'modality' => 'US',
                'description' => 'سونوگرافی داپلر رنگی - مرکز تصویربرداری سلامت پارس',
                'study_date' => now()->subDays(rand(1, 30)),
                'report' => 'سونوگرافی شکم و لگن با داپلر رنگی',
                'is_confidential' => false,
                'uploaded_by' => null,
                'metadata' => [
                    'center_name' => 'مرکز تصویربرداری سلامت پارس',
                    'equipment' => 'GE Voluson E10',
                    'services' => ['Ultrasound', 'Doppler', 'Echocardiography'],
                    'is_active' => true,
                ],
            ],

            // ============================================================
            // ✅ مرکز تصویربرداری کلینیک مهرگان
            // ============================================================
            [
                'patient_id' => null,
                'doctor_id' => null,
                'clinic_id' => $clinics['mehragan']?->id,
                'province_id' => $isfahan?->id,
                'city_id' => $isfahanCity?->id,
                'admission_id' => null,
                'appointment_id' => null,
                'image_type' => 'mri',
                'file_name' => 'mri-knee-01.jpg',
                'file_path' => 'imaging/mehragan/mri-knee-01.jpg',
                'file_size' => 1843200,
                'mime_type' => 'image/jpeg',
                'study_uid' => '1.2.840.113619.2.55.1.4.1.' . rand(100000, 999999),
                'series_uid' => '1.2.840.113619.2.55.1.4.2.' . rand(100000, 999999),
                'instance_uid' => '1.2.840.113619.2.55.1.4.3.' . rand(100000, 999999),
                'body_part' => 'knee',
                'modality' => 'MR',
                'description' => 'MRI زانو با کیفیت بالا - مرکز تصویربرداری مهرگان',
                'study_date' => now()->subDays(rand(1, 30)),
                'report' => 'تصویربرداری MRI از زانو برای تشخیص آسیب‌های ورزشی',
                'is_confidential' => false,
                'uploaded_by' => null,
                'metadata' => [
                    'center_name' => 'مرکز تصویربرداری مهرگان',
                    'equipment' => 'Philips Ingenia 1.5T',
                    'services' => ['MRI', 'MRA', 'MRCP'],
                    'is_active' => true,
                ],
            ],

            // ============================================================
            // ✅ مرکز تصویربرداری کلینیک امید
            // ============================================================
            [
                'patient_id' => null,
                'doctor_id' => null,
                'clinic_id' => $clinics['omid']?->id,
                'province_id' => $shiraz?->id,
                'city_id' => $shirazCity?->id,
                'admission_id' => null,
                'appointment_id' => null,
                'image_type' => 'xray',
                'file_name' => 'xray-01.jpg',
                'file_path' => 'imaging/omid/xray-01.jpg',
                'file_size' => 512000,
                'mime_type' => 'image/jpeg',
                'study_uid' => '1.2.840.113619.2.55.1.5.1.' . rand(100000, 999999),
                'series_uid' => '1.2.840.113619.2.55.1.5.2.' . rand(100000, 999999),
                'instance_uid' => '1.2.840.113619.2.55.1.5.3.' . rand(100000, 999999),
                'body_part' => 'chest',
                'modality' => 'DX',
                'description' => 'رادیوگرافی دیجیتال قفسه سینه - مرکز تصویربرداری امید',
                'study_date' => now()->subDays(rand(1, 30)),
                'report' => 'رادیوگرافی قفسه سینه با کیفیت بالا',
                'is_confidential' => false,
                'uploaded_by' => null,
                'metadata' => [
                    'center_name' => 'مرکز تصویربرداری امید',
                    'equipment' => 'Siemens Ysio Max',
                    'services' => ['X-Ray', 'Mammography', 'Bone Densitometry'],
                    'is_active' => true,
                ],
            ],

            // ============================================================
            // ✅ مرکز تصویربرداری کلینیک نور
            // ============================================================
            [
                'patient_id' => null,
                'doctor_id' => null,
                'clinic_id' => $clinics['noor']?->id,
                'province_id' => $mashhad?->id,
                'city_id' => $mashhadCity?->id,
                'admission_id' => null,
                'appointment_id' => null,
                'image_type' => 'ct',
                'file_name' => 'ct-brain-01.jpg',
                'file_path' => 'imaging/noor/ct-brain-01.jpg',
                'file_size' => 1740800,
                'mime_type' => 'image/jpeg',
                'study_uid' => '1.2.840.113619.2.55.1.6.1.' . rand(100000, 999999),
                'series_uid' => '1.2.840.113619.2.55.1.6.2.' . rand(100000, 999999),
                'instance_uid' => '1.2.840.113619.2.55.1.6.3.' . rand(100000, 999999),
                'body_part' => 'brain',
                'modality' => 'CT',
                'description' => 'سی‌تی اسکن مغز بدون کنتراست - مرکز تصویربرداری نور',
                'study_date' => now()->subDays(rand(1, 30)),
                'report' => 'سی‌تی اسکن مغز برای تشخیص ضایعات و خونریزی‌ها',
                'is_confidential' => false,
                'uploaded_by' => null,
                'metadata' => [
                    'center_name' => 'مرکز تصویربرداری نور',
                    'equipment' => 'GE Revolution CT',
                    'services' => ['CT Scan', 'CT Angiography', 'CT Perfusion'],
                    'is_active' => true,
                ],
            ],

            // ============================================================
            // ✅ مرکز تصویربرداری کلینیک آتیه
            // ============================================================
            [
                'patient_id' => null,
                'doctor_id' => null,
                'clinic_id' => $clinics['atiyeh']?->id,
                'province_id' => $tabriz?->id,
                'city_id' => $tabrizCity?->id,
                'admission_id' => null,
                'appointment_id' => null,
                'image_type' => 'ultrasound',
                'file_name' => 'us-pregnancy-01.jpg',
                'file_path' => 'imaging/atiyeh/us-pregnancy-01.jpg',
                'file_size' => 921600,
                'mime_type' => 'image/jpeg',
                'study_uid' => '1.2.840.113619.2.55.1.7.1.' . rand(100000, 999999),
                'series_uid' => '1.2.840.113619.2.55.1.7.2.' . rand(100000, 999999),
                'instance_uid' => '1.2.840.113619.2.55.1.7.3.' . rand(100000, 999999),
                'body_part' => 'pelvis',
                'modality' => 'US',
                'description' => 'سونوگرافی بارداری سه بعدی - مرکز تصویربرداری آتیه',
                'study_date' => now()->subDays(rand(1, 30)),
                'report' => 'سونوگرافی سه بعدی جنین برای بررسی سلامت مادر و جنین',
                'is_confidential' => false,
                'uploaded_by' => null,
                'metadata' => [
                    'center_name' => 'مرکز تصویربرداری آتیه',
                    'equipment' => 'Samsung WS80A Elite',
                    'services' => ['Ultrasound', '3D/4D Ultrasound', 'Fetal Echo'],
                    'is_active' => true,
                ],
            ],
        ];

        foreach ($imagingCenters as $data) {
            MedicalImage::updateOrCreate(
                [
                    'file_path' => $data['file_path'],
                ],
                $data
            );
        }

        $this->command->info('✅ ۶ مرکز تصویربرداری با موفقیت ایجاد شدند.');
        $this->command->info('   📋 توزیع:');
        $this->command->info('      🏥 دکتر وب: ۲ مرکز (MRI, CT)');
        $this->command->info('      🏥 سلامت پارس: ۱ مرکز (Ultrasound)');
        $this->command->info('      🏥 مهرگان: ۱ مرکز (MRI)');
        $this->command->info('      🏥 امید: ۱ مرکز (X-Ray)');
        $this->command->info('      🏥 نور: ۱ مرکز (CT)');
        $this->command->info('      🏥 آتیه: ۱ مرکز (Ultrasound 3D)');
    }
}
