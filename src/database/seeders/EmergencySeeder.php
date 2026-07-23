<?php
// database/seeders/EmergencySeeder.php

namespace Database\Seeders;

use App\Models\Emergency\EmergencyPatient;
use App\Models\Patient;
use App\Models\User;
use App\Models\Clinic;
use App\Models\Province;
use App\Models\City;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EmergencySeeder extends Seeder
{
    public function run(): void
    {
        $clinics = Clinic::all()->keyBy('slug');
        $tehran = Province::where('name', 'تهران')->first();
        $tehranCity = City::where('name', 'تهران')->where('province_id', $tehran?->id)->first();

        // ایجاد کاربران و بیماران تست
        $patients = [];
        for ($i = 1; $i <= 5; $i++) {
            $user = User::create([
                'name' => "بیمار اورژانس {$i}",
                'mobile' => "0912345678{$i}",
                'password' => Hash::make('password123'),
                'is_active' => true,
            ]);

            $patients[] = Patient::create([
                'user_id' => $user->id,
                'full_name' => "بیمار اورژانس {$i}",
                'national_code' => str_pad($i, 10, '0', STR_PAD_LEFT),
                'phone' => "0912345678{$i}",
                'is_active' => true,
            ]);
        }

        // سطوح تریاز
        $triageLevels = ['red', 'yellow', 'green', 'blue'];

        // وضعیت‌ها
        $statuses = ['waiting', 'in_triage', 'in_exam', 'in_treatment', 'dispatched', 'arrived', 'admitted', 'discharged'];

        $emergencies = [
            // بحرانی (Red) - نیاز به اقدام فوری
            [
                'patient_id' => $patients[0]?->id,
                'clinic_id' => $clinics['dr-web']?->id,
                'province_id' => $tehran?->id,
                'city_id' => $tehranCity?->id,
                'triage_level' => 'red',
                'status' => 'in_treatment',
                'chief_complaint' => 'درد شدید قفسه سینه و تنگی نفس',
                'history_of_present_illness' => 'درد قفسه سینه که به بازوی چپ انتشار پیدا کرده',
                'vital_signs' => [
                    'blood_pressure' => '180/110',
                    'heart_rate' => 110,
                    'respiratory_rate' => 22,
                    'temperature' => 37.2,
                    'oxygen_saturation' => 92,
                ],
                'allergies' => 'آسپرین',
                'medications' => 'نیتروگلیسیرین',
                'past_medical_history' => 'فشار خون بالا، دیابت نوع ۲',
                'emergency_contact_name' => 'مریم احمدی',
                'emergency_contact_phone' => '09123456789',
                'emergency_contact_relation' => 'همسر',
                'request_address' => 'تهران، خیابان ولیعصر، پلاک ۱۲۳',
                'notes' => 'بیمار سابقه آنژین صدری دارد',
                'arrival_time' => now()->subMinutes(30),
                'disposition' => 'admitted',
                'disposition_time' => now()->subMinutes(10),
            ],
            // فوری (Yellow)
            [
                'patient_id' => $patients[1]?->id,
                'clinic_id' => $clinics['dr-web']?->id,
                'province_id' => $tehran?->id,
                'city_id' => $tehranCity?->id,
                'triage_level' => 'yellow',
                'status' => 'in_exam',
                'chief_complaint' => 'درد شدید شکم و تهوع',
                'history_of_present_illness' => 'درد در ناحیه راست و پایین شکم از ۱۲ ساعت پیش شروع شده',
                'vital_signs' => [
                    'blood_pressure' => '140/90',
                    'heart_rate' => 95,
                    'respiratory_rate' => 18,
                    'temperature' => 38.5,
                    'oxygen_saturation' => 98,
                ],
                'allergies' => 'پنی‌سیلین',
                'medications' => 'متفورمین',
                'past_medical_history' => 'دیابت نوع ۲',
                'emergency_contact_name' => 'علی محمدی',
                'emergency_contact_phone' => '09123456780',
                'emergency_contact_relation' => 'پدر',
                'request_address' => 'تهران، خیابان انقلاب، پلاک ۴۵',
                'notes' => 'احتمال آپاندیسیت حاد',
                'arrival_time' => now()->subMinutes(45),
            ],
            // معمولی (Green)
            [
                'patient_id' => $patients[2]?->id,
                'clinic_id' => $clinics['salamat-pars']?->id,
                'province_id' => $tehran?->id,
                'city_id' => $tehranCity?->id,
                'triage_level' => 'green',
                'status' => 'waiting',
                'chief_complaint' => 'سرفه و گلودرد',
                'history_of_present_illness' => 'سرفه خشک و گلودرد از ۳ روز پیش شروع شده',
                'vital_signs' => [
                    'blood_pressure' => '120/80',
                    'heart_rate' => 75,
                    'respiratory_rate' => 16,
                    'temperature' => 37.8,
                    'oxygen_saturation' => 99,
                ],
                'allergies' => null,
                'medications' => null,
                'past_medical_history' => null,
                'emergency_contact_name' => 'سارا کریمی',
                'emergency_contact_phone' => '09123456781',
                'emergency_contact_relation' => 'دختر',
                'request_address' => 'تهران، خیابان انقلاب، پلاک ۴۵',
                'notes' => 'احتمال عفونت ویروسی',
                'arrival_time' => now()->subMinutes(60),
            ],
            // غیرفوری (Blue)
            [
                'patient_id' => $patients[3]?->id,
                'clinic_id' => $clinics['mehragan']?->id,
                'province_id' => $tehran?->id,
                'city_id' => $tehranCity?->id,
                'triage_level' => 'blue',
                'status' => 'waiting',
                'chief_complaint' => 'خارش پوستی',
                'history_of_present_illness' => 'خارش در ناحیه دست و پا از ۲ روز پیش شروع شده',
                'vital_signs' => [
                    'blood_pressure' => '115/75',
                    'heart_rate' => 70,
                    'respiratory_rate' => 15,
                    'temperature' => 36.8,
                    'oxygen_saturation' => 100,
                ],
                'allergies' => 'گرده گیاهان',
                'medications' => 'لوراتادین',
                'past_medical_history' => 'آلرژی فصلی',
                'emergency_contact_name' => 'رضا حسینی',
                'emergency_contact_phone' => '09123456782',
                'emergency_contact_relation' => 'برادر',
                'request_address' => 'اصفهان، خیابان چهارباغ، پلاک ۷۸',
                'notes' => 'احتمال کهیر یا درماتیت',
                'arrival_time' => now()->subMinutes(90),
            ],
            // با آمبولانس اعزام شده
            [
                'patient_id' => $patients[4]?->id,
                'clinic_id' => $clinics['dr-web']?->id,
                'province_id' => $tehran?->id,
                'city_id' => $tehranCity?->id,
                'triage_level' => 'red',
                'status' => 'dispatched',
                'chief_complaint' => 'تصادف رانندگی، خونریزی شدید',
                'history_of_present_illness' => 'تصادف با موتور سیکلت، خونریزی از ناحیه پا',
                'vital_signs' => [
                    'blood_pressure' => '90/60',
                    'heart_rate' => 120,
                    'respiratory_rate' => 24,
                    'temperature' => 36.5,
                    'oxygen_saturation' => 88,
                ],
                'allergies' => null,
                'medications' => null,
                'past_medical_history' => null,
                'emergency_contact_name' => 'نرگس رضایی',
                'emergency_contact_phone' => '09123456783',
                'emergency_contact_relation' => 'همسر',
                'request_address' => 'تهران، خیابان ولیعصر، پلاک ۱۲۵',
                'ambulance_number' => 'AMB-001',
                'ambulance_team' => 'تیم اورژانس ۱',
                'dispatched_at' => now()->subMinutes(10),
                'notes' => 'نیاز به انتقال سریع به بیمارستان',
                'arrival_time' => now()->subMinutes(20),
            ],
        ];

        foreach ($emergencies as $data) {
            EmergencyPatient::create($data);
        }

        $this->command->info('✅ درخواست‌های اورژانس با موفقیت ایجاد شدند.');
        $this->command->info('   📊 توزیع:');
        $this->command->info('      🔴 بحرانی: ۲ درخواست');
        $this->command->info('      🟡 فوری: ۱ درخواست');
        $this->command->info('      🟢 معمولی: ۱ درخواست');
        $this->command->info('      🔵 غیرفوری: ۱ درخواست');
    }
}
