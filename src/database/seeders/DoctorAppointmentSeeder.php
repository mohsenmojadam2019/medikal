<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Appointment;
use App\Models\Patient;
use Carbon\Carbon;

class DoctorAppointmentSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('🚀 Starting DoctorAppointmentSeeder...');

        // ============================================================
        // 1. ایجاد یا پیدا کردن کاربر پزشک
        // ============================================================
        $user = User::firstOrCreate(
            ['mobile' => '09123456790'],
            [
                'name' => 'دکتر علی محمدی',
                'email' => 'doctor@clinic.com',
                'password' => bcrypt('password123'),
                'is_active' => true,
            ]
        );

        $this->command->info('✅ User created: ' . $user->name);

        // ============================================================
        // 2. ایجاد یا پیدا کردن پزشک
        // ============================================================
        $doctor = Doctor::firstOrCreate(
            ['user_id' => $user->id],
            [
                'specialty_id' => 1,
                'license_number' => '123456',
                'consultation_fee' => 150000,
                'visit_duration' => 30,
                'is_available' => true,
                'is_verified' => true,
                'is_active' => true,
                'experience_years' => 10,
                'clinic_name' => 'کلینیک دکتر محمدی',
                'clinic_address' => 'تهران، خیابان ولیعصر، پلاک ۱۲۳',
                'clinic_phone' => '021-12345678',
                'clinic_email' => 'doctor@clinic.com',
            ]
        );

        $this->command->info('✅ Doctor: ' . $doctor->id);

        // ============================================================
        // 3. ایجاد برنامه کاری برای پزشک
        // ============================================================
        $schedules = [
            ['day' => 0, 'start' => '09:00:00', 'end' => '17:00:00', 'break_start' => '13:00:00', 'break_end' => '14:00:00', 'slots' => 8],
            ['day' => 1, 'start' => '09:00:00', 'end' => '17:00:00', 'break_start' => '13:00:00', 'break_end' => '14:00:00', 'slots' => 8],
            ['day' => 2, 'start' => '09:00:00', 'end' => '17:00:00', 'break_start' => '13:00:00', 'break_end' => '14:00:00', 'slots' => 8],
            ['day' => 3, 'start' => '09:00:00', 'end' => '17:00:00', 'break_start' => '13:00:00', 'break_end' => '14:00:00', 'slots' => 8],
            ['day' => 4, 'start' => '09:00:00', 'end' => '13:00:00', 'break_start' => null, 'break_end' => null, 'slots' => 4],
        ];

        $scheduleCount = 0;
        foreach ($schedules as $schedule) {
            DoctorSchedule::updateOrCreate(
                [
                    'doctor_id' => $doctor->id,
                    'day_of_week' => $schedule['day'],
                ],
                [
                    'start_time' => $schedule['start'],
                    'end_time' => $schedule['end'],
                    'break_start' => $schedule['break_start'],
                    'break_end' => $schedule['break_end'],
                    'slot_duration' => 30,
                    'max_slots_per_day' => $schedule['slots'],
                    'is_active' => true,
                ]
            );
            $scheduleCount++;
        }

        $this->command->info('✅ Schedules: ' . $scheduleCount);

        // ============================================================
        // 4. ایجاد یا پیدا کردن بیمار تست
        // ============================================================
        $patientUser = User::firstOrCreate(
            ['mobile' => '09034325329'],
            [
                'name' => 'بیمار تست',
                'email' => 'patient@test.com',
                'password' => bcrypt('password123'),
                'is_active' => true,
            ]
        );

        $this->command->info('✅ Patient user: ' . $patientUser->name);

        $patient = Patient::firstOrCreate(
            ['national_code' => '1234567890'],
            [
                'user_id' => $patientUser->id,
                'phone' => '09034325329',
                'is_active' => true,
                'verified_at' => now(),
            ]
        );

        $this->command->info('✅ Patient: ' . $patient->id);

        // ============================================================
        // 5. ایجاد نوبت‌های تست برای ۴ روز آینده
        // ============================================================
        $today = Carbon::now();
        $timeSlots = ['09:00:00', '09:30:00', '10:00:00', '10:30:00', '11:00:00', '11:30:00', '12:00:00', '12:30:00'];
        $appointmentCount = 0;
        
        for ($day = 0; $day < 4; $day++) {
            $date = $today->copy()->addDays($day);
            $dayOfWeek = $date->dayOfWeek;
            
            if ($dayOfWeek > 4) continue;
            
            $slotsToCreate = ($dayOfWeek == 4) ? 4 : 8;
            for ($i = 0; $i < $slotsToCreate; $i++) {
                $time = $timeSlots[$i];
                
                $exists = Appointment::where('doctor_id', $doctor->id)
                    ->whereDate('date', $date->format('Y-m-d'))
                    ->where('start_time', $time)
                    ->exists();
                
                if (!$exists) {
                    Appointment::create([
                        'tenant_id' => 1,
                        'patient_id' => $patient->id,
                        'doctor_id' => $doctor->id,
                        'date' => $date->format('Y-m-d'),
                        'start_time' => $time,
                        'end_time' => Carbon::parse($time)->addMinutes(30)->format('H:i:s'),
                        'duration' => 30,
                        'status' => Appointment::STATUS_PENDING,
                        'type' => 'in_person',
                        'fee' => $doctor->consultation_fee,
                        'discount' => 0,
                        'final_price' => $doctor->consultation_fee,
                        'payment_status' => Appointment::PAYMENT_PENDING,
                        'notes' => "نوبت تست - روز {$date->format('Y-m-d')} ساعت {$time}",
                    ]);
                    $appointmentCount++;
                }
            }
        }

        $this->command->info('✅ Appointments: ' . $appointmentCount);

        // ============================================================
        // 6. خلاصه
        // ============================================================
        $totalAppointments = Appointment::where('doctor_id', $doctor->id)->count();
        
        $this->command->info('========================================');
        $this->command->info('📊 SUMMARY:');
        $this->command->info('✅ Doctor ID: ' . $doctor->id);
        $this->command->info('✅ Patient ID: ' . $patient->id);
        $this->command->info('✅ Total Appointments: ' . $totalAppointments);
        $this->command->info('========================================');
        $this->command->info('🎉 Seeder completed successfully!');
    }
}
