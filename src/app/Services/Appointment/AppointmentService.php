<?php

namespace App\Services\Appointment;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\DoctorSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppointmentService
{
    protected $tenantId;

    public function __construct()
    {
        $this->tenantId = session('tenant_id', 1);
    }

    /**
     * دریافت زمان‌های خالی پزشک
     */
    public function getAvailableSlots(Doctor $doctor, string $date): array
    {
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        $schedule = DoctorSchedule::where('doctor_id', $doctor->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->first();

        if (is_null($schedule)) {
            return [
                'available' => false,
                'message' => 'پزشک در این روز کاری ندارد',
                'slots' => []
            ];
        }

        $bookedAppointments = Appointment::where('doctor_id', $doctor->id)
            ->where('tenant_id', $this->tenantId)
            ->whereDate('date', $date)
            ->whereIn('status', [
                Appointment::STATUS_PENDING,
                Appointment::STATUS_CONFIRMED,
                Appointment::STATUS_ARRIVED,
                Appointment::STATUS_IN_PROGRESS
            ])
            ->pluck('start_time')
            ->toArray();

        $start = Carbon::parse($schedule->start_time);
        $end = Carbon::parse($schedule->end_time);
        $slotDuration = $schedule->slot_duration;
        $allSlots = [];

        while ($start < $end) {
            $slotEnd = clone $start;
            $slotEnd->addMinutes($slotDuration);

            $isBreak = false;
            if ($schedule->break_start && $schedule->break_end) {
                $breakStart = Carbon::parse($schedule->break_start);
                $breakEnd = Carbon::parse($schedule->break_end);
                if ($start >= $breakStart && $start < $breakEnd) {
                    $isBreak = true;
                }
            }

            if ($isBreak == false) {
                $isBooked = in_array($start->format('H:i:s'), $bookedAppointments);
                $allSlots[] = [
                    'time' => $start->format('H:i'),
                    'start_time' => $start->format('H:i:s'),
                    'end_time' => $slotEnd->format('H:i:s'),
                    'is_available' => ($isBooked == false),
                    'is_booked' => $isBooked,
                ];
            }

            $start->addMinutes($slotDuration);
        }

        $availableSlots = array_filter($allSlots, function($slot) {
            return $slot['is_available'] === true;
        });

        return [
            'available' => true,
            'date' => $date,
            'doctor' => [
                'id' => $doctor->id,
                'name' => $doctor->full_name,
                'specialty' => $doctor->specialty?->name,
                'consultation_fee' => $doctor->consultation_fee,
            ],
            'slots' => array_values($availableSlots),
            'total_slots' => count($allSlots),
            'available_slots' => count($availableSlots),
        ];
    }

    /**
     * رزرو نوبت جدید با قفل تراکنشی
     */
    public function bookAppointment(array $data): Appointment
    {
        return DB::transaction(function () use ($data) {
            // 1. قفل کردن جدول پزشک برای جلوگیری از تغییر همزمان
            $doctor = Doctor::where('id', $data['doctor_id'])
                ->lockForUpdate()
                ->first();

            if (!$doctor) {
                throw new \Exception('پزشک یافت نشد');
            }

            // 2. قفل کردن جدول زمان‌بندی پزشک
            $dayOfWeek = Carbon::parse($data['date'])->dayOfWeek;
            $schedule = DoctorSchedule::where('doctor_id', $doctor->id)
                ->where('day_of_week', $dayOfWeek)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (!$schedule) {
                throw new \Exception('پزشک در این روز کاری ندارد');
            }

            // 3. بررسی و قفل کردن نوبت‌های موجود برای جلوگیری از تداخل
            $existingAppointment = Appointment::where('doctor_id', $doctor->id)
                ->where('tenant_id', $this->tenantId)
                ->whereDate('date', $data['date'])
                ->where('start_time', $data['start_time'])
                ->whereIn('status', [
                    Appointment::STATUS_PENDING,
                    Appointment::STATUS_CONFIRMED,
                    Appointment::STATUS_ARRIVED,
                    Appointment::STATUS_IN_PROGRESS
                ])
                ->lockForUpdate() // قفل کردن رکوردهای موجود
                ->first();

            if ($existingAppointment) {
                throw new \Exception('این زمان قبلاً توسط شخص دیگری رزرو شده است');
            }

            // 4. اعتبارسنجی کامل زمان
            $this->validateSlotAvailability($doctor, $schedule, $data['date'], $data['start_time']);

            // 5. ایجاد یا دریافت بیمار
            $patient = $this->getOrCreatePatient($data);

            // 6. ایجاد نوبت جدید
            $fee = $doctor->consultation_fee ?? 0;

            $appointment = Appointment::create([
                'tenant_id' => $this->tenantId,
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'date' => $data['date'],
                'start_time' => $data['start_time'],
                'duration' => $doctor->visit_duration ?? 30,
                'status' => Appointment::STATUS_PENDING,
                'type' => $data['type'] ?? 'in_person',
                'fee' => $fee,
                'discount' => $data['discount'] ?? 0,
                'final_price' => $fee - ($data['discount'] ?? 0),
                'payment_status' => Appointment::PAYMENT_PENDING,
                'notes' => $data['notes'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);

            // 7. محاسبه زمان پایان
            $endTime = Carbon::parse($appointment->start_time)
                ->addMinutes($appointment->duration)
                ->format('H:i:s');
            $appointment->update(['end_time' => $endTime]);

            Log::info('نوبت جدید رزرو شد', [
                'appointment_id' => $appointment->id,
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'date' => $data['date'],
                'time' => $data['start_time'],
                'transaction_id' => DB::transactionLevel(),
            ]);

            return $appointment->load(['patient.user', 'doctor.user', 'doctor.specialty']);
        }, 3); // تلاش مجدد ۳ بار در صورت برخورد قفل
    }

    /**
     * تایید نوبت
     */
    public function confirmAppointment(Appointment $appointment): Appointment
    {
        return DB::transaction(function () use ($appointment) {
            $appointment = Appointment::where('id', $appointment->id)
                ->lockForUpdate()
                ->first();

            if (!$appointment) {
                throw new \Exception('نوبت یافت نشد');
            }

            if ($appointment->status !== Appointment::STATUS_PENDING) {
                throw new \Exception('فقط نوبت‌های در انتظار تایید قابل تایید هستند');
            }

            $appointment->update(['status' => Appointment::STATUS_CONFIRMED]);

            Log::info('نوبت تایید شد', [
                'appointment_id' => $appointment->id,
                'doctor_id' => $appointment->doctor_id,
            ]);

            return $appointment->fresh();
        });
    }

    /**
     * لغو نوبت
     */
    public function cancelAppointment(Appointment $appointment, string $reason = null): Appointment
    {
        return DB::transaction(function () use ($appointment, $reason) {
            $appointment = Appointment::where('id', $appointment->id)
                ->lockForUpdate()
                ->first();

            if (!$appointment) {
                throw new \Exception('نوبت یافت نشد');
            }

            if ($appointment->canCancel() == false) {
                throw new \Exception('امکان لغو این نوبت وجود ندارد');
            }

            $appointment->update([
                'status' => Appointment::STATUS_CANCELLED,
                'notes' => $reason ? "لغو شده: {$reason}" : $appointment->notes,
            ]);

            Log::info('نوبت لغو شد', [
                'appointment_id' => $appointment->id,
                'reason' => $reason,
            ]);

            return $appointment->fresh();
        });
    }

    /**
     * تغییر زمان نوبت
     */
    public function rescheduleAppointment(Appointment $appointment, array $data): Appointment
    {
        return DB::transaction(function () use ($appointment, $data) {
            // قفل کردن نوبت فعلی
            $appointment = Appointment::where('id', $appointment->id)
                ->lockForUpdate()
                ->first();

            if (!$appointment) {
                throw new \Exception('نوبت یافت نشد');
            }

            if ($appointment->canReschedule() == false) {
                throw new \Exception('امکان تغییر زمان این نوبت وجود ندارد');
            }

            // قفل کردن پزشک
            $doctor = Doctor::where('id', $appointment->doctor_id)
                ->lockForUpdate()
                ->first();

            if (!$doctor) {
                throw new \Exception('پزشک یافت نشد');
            }

            // بررسی زمان جدید
            $dayOfWeek = Carbon::parse($data['date'])->dayOfWeek;
            $schedule = DoctorSchedule::where('doctor_id', $doctor->id)
                ->where('day_of_week', $dayOfWeek)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (!$schedule) {
                throw new \Exception('پزشک در این روز کاری ندارد');
            }

            // بررسی تداخل با نوبت‌های دیگر
            $conflict = Appointment::where('doctor_id', $doctor->id)
                ->where('tenant_id', $this->tenantId)
                ->whereDate('date', $data['date'])
                ->where('start_time', $data['start_time'])
                ->where('id', '!=', $appointment->id)
                ->whereIn('status', [
                    Appointment::STATUS_PENDING,
                    Appointment::STATUS_CONFIRMED,
                    Appointment::STATUS_ARRIVED,
                    Appointment::STATUS_IN_PROGRESS
                ])
                ->lockForUpdate()
                ->exists();

            if ($conflict) {
                throw new \Exception('زمان انتخابی توسط شخص دیگری رزرو شده است');
            }

            $appointment->update([
                'date' => $data['date'],
                'start_time' => $data['start_time'],
                'status' => Appointment::STATUS_PENDING,
            ]);

            $endTime = Carbon::parse($appointment->start_time)
                ->addMinutes($appointment->duration)
                ->format('H:i:s');
            $appointment->update(['end_time' => $endTime]);

            Log::info('زمان نوبت تغییر کرد', [
                'appointment_id' => $appointment->id,
                'new_date' => $data['date'],
                'new_time' => $data['start_time'],
            ]);

            return $appointment->fresh();
        });
    }

    /**
     * شروع ویزیت (حضور بیمار)
     */
    public function startAppointment(Appointment $appointment): Appointment
    {
        return DB::transaction(function () use ($appointment) {
            $appointment = Appointment::where('id', $appointment->id)
                ->lockForUpdate()
                ->first();

            if (!$appointment) {
                throw new \Exception('نوبت یافت نشد');
            }

            if ($appointment->status !== Appointment::STATUS_CONFIRMED) {
                throw new \Exception('فقط نوبت‌های تایید شده قابل شروع هستند');
            }

            $appointment->update(['status' => Appointment::STATUS_ARRIVED]);

            Log::info('حضور بیمار ثبت شد', [
                'appointment_id' => $appointment->id,
            ]);

            return $appointment->fresh();
        });
    }

    /**
     * پایان ویزیت
     */
    public function completeAppointment(Appointment $appointment): Appointment
    {
        return DB::transaction(function () use ($appointment) {
            $appointment = Appointment::where('id', $appointment->id)
                ->lockForUpdate()
                ->first();

            if (!$appointment) {
                throw new \Exception('نوبت یافت نشد');
            }

            if ($appointment->status !== Appointment::STATUS_ARRIVED) {
                throw new \Exception('فقط نوبت‌های حاضر قابل پایان هستند');
            }

            $appointment->update(['status' => Appointment::STATUS_COMPLETED]);

            Log::info('ویزیت پایان یافت', [
                'appointment_id' => $appointment->id,
            ]);

            return $appointment->fresh();
        });
    }

    /**
     * بیمار حاضر نشده
     */
    public function markNoShow(Appointment $appointment): Appointment
    {
        return DB::transaction(function () use ($appointment) {
            $appointment = Appointment::where('id', $appointment->id)
                ->lockForUpdate()
                ->first();

            if (!$appointment) {
                throw new \Exception('نوبت یافت نشد');
            }

            if ($appointment->status === Appointment::STATUS_COMPLETED) {
                throw new \Exception('نوبت قبلاً انجام شده است');
            }

            $appointment->update(['status' => Appointment::STATUS_NO_SHOW]);

            Log::info('بیمار حاضر نشد', [
                'appointment_id' => $appointment->id,
            ]);

            return $appointment->fresh();
        });
    }

    /**
     * لیست نوبت‌های بیمار
     */
    public function patientAppointments(Patient $patient, array $filters = [], int $perPage = 15)
    {
        $query = Appointment::where('patient_id', $patient->id)
            ->where('tenant_id', $this->tenantId)
            ->with(['doctor.user', 'doctor.specialty']);

        if (isset($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (isset($filters['upcoming']) && $filters['upcoming']) {
            $query->upcoming();
        }

        if (isset($filters['past']) && $filters['past']) {
            $query->past();
        }

        return $query->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate($perPage);
    }

    /**
     * لیست نوبت‌های پزشک
     */
    public function doctorAppointments(Doctor $doctor, array $filters = [], int $perPage = 15)
    {
        $query = Appointment::where('doctor_id', $doctor->id)
            ->where('tenant_id', $this->tenantId)
            ->with(['patient.user']);

        if (isset($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (isset($filters['date'])) {
            $query->whereDate('date', $filters['date']);
        }

        if (isset($filters['upcoming']) && $filters['upcoming']) {
            $query->upcoming();
        }

        return $query->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate($perPage);
    }

    /**
     * اعتبارسنجی زمان نوبت (داخلی)
     */
    protected function validateSlotAvailability(
        Doctor $doctor,
        DoctorSchedule $schedule,
        string $date,
        string $startTime
    ): void {
        $start = Carbon::parse($startTime);
        $scheduleStart = Carbon::parse($schedule->start_time);
        $scheduleEnd = Carbon::parse($schedule->end_time);

        // بررسی ساعات کاری
        if ($start < $scheduleStart || $start >= $scheduleEnd) {
            throw new \Exception('زمان انتخابی خارج از ساعات کاری پزشک است');
        }

        // بررسی زمان استراحت
        if ($schedule->break_start && $schedule->break_end) {
            $breakStart = Carbon::parse($schedule->break_start);
            $breakEnd = Carbon::parse($schedule->break_end);
            if ($start >= $breakStart && $start < $breakEnd) {
                throw new \Exception('زمان انتخابی در زمان استراحت پزشک است');
            }
        }

        // بررسی بازه‌های ۳۰ دقیقه‌ای
        if ($start->minute % 30 != 0) {
            throw new \Exception('زمان انتخابی باید در بازه‌های ۳۰ دقیقه‌ای باشد');
        }
    }

    /**
     * دریافت یا ایجاد بیمار
     */
    protected function getOrCreatePatient(array $data): Patient
    {
        if (isset($data['patient_id'])) {
            return Patient::where('tenant_id', $this->tenantId)->findOrFail($data['patient_id']);
        }

        if (auth()->check()) {
            $user = auth()->user();
            $patient = Patient::where('tenant_id', $this->tenantId)
                ->where('user_id', $user->id)
                ->first();
            
            if ($patient) {
                return $patient;
            }

            return Patient::create([
                'tenant_id' => $this->tenantId,
                'user_id' => $user->id,
                'national_code' => $data['national_code'] ?? null,
                'phone' => $data['phone'] ?? $user->mobile ?? null,
                'is_active' => true,
                'verified_at' => now(),
            ]);
        }

        if (isset($data['national_code']) && !empty($data['national_code'])) {
            $patient = Patient::where('tenant_id', $this->tenantId)
                ->where('national_code', $data['national_code'])
                ->first();
            
            if ($patient) {
                return $patient;
            }
        }

        $userData = [
            'name' => $data['patient_name'] ?? 'بیمار',
            'mobile' => $data['phone'] ?? $data['mobile'] ?? null,
            'is_active' => true,
        ];

        if (isset($data['email'])) {
            $userData['email'] = $data['email'];
        }

        $user = \App\Models\User::create($userData);

        return Patient::create([
            'tenant_id' => $this->tenantId,
            'user_id' => $user->id,
            'national_code' => $data['national_code'] ?? null,
            'phone' => $data['phone'] ?? $data['mobile'] ?? null,
            'is_active' => true,
            'verified_at' => now(),
        ]);
    }
}
