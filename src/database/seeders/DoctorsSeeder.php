<?php
// database/seeders/DoctorsSeeder.php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\User;
use App\Models\Clinic;
use App\Models\Specialty;
use App\Models\Province;
use App\Models\City;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DoctorsSeeder extends Seeder
{
    public function run(): void
    {
        $clinics = Clinic::all()->keyBy('slug');
        $specialties = Specialty::all()->keyBy('slug');
        $tehran = Province::where('name', 'تهران')->first();
        $tehranCity = City::where('name', 'تهران')->where('province_id', $tehran?->id)->first();

        $doctors = [
            // ✅ پزشکان کلینیک دکتر وب (۳ نفر)
            [
                'name' => 'دکتر علی محمدی',
                'mobile' => '09123456781',
                'email' => 'ali@drweb.com',
                'clinic_slug' => 'dr-web',
                'specialty_slug' => 'dakheli',
                'license_number' => '123456',
                'experience_years' => 15,
                'consultation_fee' => 150000,
                'appointment_fee_type' => 'paid',
                'appointment_fee_amount' => 150000,
                'bio' => 'متخصص داخلی با ۱۵ سال سابقه',
                'is_available' => true,
                'is_verified' => true,
            ],
            [
                'name' => 'دکتر سارا کریمی',
                'mobile' => '09123456782',
                'email' => 'sara@drweb.com',
                'clinic_slug' => 'dr-web',
                'specialty_slug' => 'ghalb',
                'license_number' => '123457',
                'experience_years' => 12,
                'consultation_fee' => 200000,
                'appointment_fee_type' => 'paid',
                'appointment_fee_amount' => 200000,
                'bio' => 'متخصص قلب و عروق با ۱۲ سال سابقه',
                'is_available' => true,
                'is_verified' => true,
            ],
            [
                'name' => 'دکتر رضا حسینی',
                'mobile' => '09123456783',
                'email' => 'reza@drweb.com',
                'clinic_slug' => 'dr-web',
                'specialty_slug' => 'koodakan',
                'license_number' => '123458',
                'experience_years' => 10,
                'consultation_fee' => 120000,
                'appointment_fee_type' => 'paid',
                'appointment_fee_amount' => 120000,
                'bio' => 'متخصص کودکان با ۱۰ سال سابقه',
                'is_available' => true,
                'is_verified' => true,
            ],
            // ✅ پزشکان کلینیک سلامت پارس (۱ نفر)
            [
                'name' => 'دکتر زهرا احمدی',
                'mobile' => '09123456784',
                'email' => 'zahra@salamatpars.com',
                'clinic_slug' => 'salamat-pars',
                'specialty_slug' => 'zanan',
                'license_number' => '123459',
                'experience_years' => 8,
                'consultation_fee' => 180000,
                'appointment_fee_type' => 'paid',
                'appointment_fee_amount' => 180000,
                'bio' => 'متخصص زنان و زایمان با ۸ سال سابقه',
                'is_available' => true,
                'is_verified' => true,
            ],
            // ✅ پزشکان کلینیک مهرگان (۱ نفر)
            [
                'name' => 'دکتر محمد نوری',
                'mobile' => '09123456785',
                'email' => 'mohammad@mehragan.com',
                'clinic_slug' => 'mehragan',
                'specialty_slug' => 'ortopedi',
                'license_number' => '123460',
                'experience_years' => 14,
                'consultation_fee' => 170000,
                'appointment_fee_type' => 'paid',
                'appointment_fee_amount' => 170000,
                'bio' => 'متخصص ارتوپدی با ۱۴ سال سابقه',
                'is_available' => true,
                'is_verified' => true,
            ],
            // ✅ پزشکان کلینیک امید (۱ نفر)
            [
                'name' => 'دکتر لیلا رضایی',
                'mobile' => '09123456786',
                'email' => 'leila@omid.com',
                'clinic_slug' => 'omid',
                'specialty_slug' => 'asab',
                'license_number' => '123461',
                'experience_years' => 11,
                'consultation_fee' => 160000,
                'appointment_fee_type' => 'paid',
                'appointment_fee_amount' => 160000,
                'bio' => 'متخصص مغز و اعصاب با ۱۱ سال سابقه',
                'is_available' => true,
                'is_verified' => true,
            ],
        ];

        foreach ($doctors as $doctorData) {
            // ایجاد کاربر
            $user = User::updateOrCreate(
                ['mobile' => $doctorData['mobile']],
                [
                    'name' => $doctorData['name'],
                    'email' => $doctorData['email'],
                    'password' => Hash::make('password123'),
                    'is_active' => true,
                ]
            );

            // پیدا کردن کلینیک
            $clinic = $clinics[$doctorData['clinic_slug']] ?? null;

            // ایجاد پزشک
            Doctor::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'clinic_id' => $clinic?->id,
                    'specialty_id' => $specialties[$doctorData['specialty_slug']]?->id,
                    'license_number' => $doctorData['license_number'],
                    'clinic_name' => $clinic?->name,
                    'clinic_address' => $clinic?->address,
                    'province_id' => $tehran?->id,
                    'city_id' => $tehranCity?->id,
                    'experience_years' => $doctorData['experience_years'],
                    'consultation_fee' => $doctorData['consultation_fee'],
                    'appointment_fee_type' => $doctorData['appointment_fee_type'],
                    'appointment_fee_amount' => $doctorData['appointment_fee_amount'],
                    'bio' => $doctorData['bio'],
                    'is_available' => $doctorData['is_available'],
                    'is_verified' => $doctorData['is_verified'],
                    'is_active' => true,
                    'visit_duration' => 30,
                ]
            );
        }

        $this->command->info('✅ ۶ پزشک با موفقیت ایجاد شدند.');
    }
}
