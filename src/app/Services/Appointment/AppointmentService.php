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
    /**
     * دریافت زمان‌های آزاد پزشک در یک تاریخ مشخص
     */
    public function getAvailableSlots(Doctor $doctor, string $date): array
    {
        // 1. دریافت زمانبندی پزشک برای روز مورد نظر
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        $schedule = DoctorSchedule::where('doctor_id', $doctor->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->first();

        if (!$schedule) {
            return [
                'available' => false,
                'message' => 'پزشک در این روز کاری ندارد',
                'slots' => []
            ];
        }

        // 2. دریافت نوبت‌های رزرو شده در این تاریخ
        $bookedAppointments = Appointment::where('doctor_id', $doctor->id)
            ->whereDate('date', $date)
            ->whereIn('status', [
                Appointment::STATUS_PENDING,
                Appointment::STATUS_CONFIRMED,
                Appointment::STATUS_ARRIVED,
                Appointment::STATUS_IN_PROGRESS
            ])
            ->pluck('start_time')
            ->toArray();

        // 3. تولید همه بازه‌های زمانی ممکن
        $start = Carbon::parse($schedule->start_time);
        $end = Carbon::parse($schedule->end_time);
        $slotDuration = $schedule->slot_duration; // دقیقه
        $allSlots = [];

        while ($start < $end) {
            $slotEnd = clone $start;
            $slotEnd->addMinutes($slotDuration);

            // چک کردن زمان استراحت
            $isBreak = false;
            if ($schedule->break_start && $schedule->break_end) {
                $breakStart = Carbon::parse($schedule->break_start);
                $breakEnd = Carbon::parse($schedule->break_end);
                if ($start >= $breakStart && $start < $breakEnd) {
                    $isBreak = true;
                }
            }

            if (!$isBreak) {
                $timeString = $start->format('H:i');
                $isBooked = in_array($start->format('H:i:s'), $bookedAppointments);

                $allSlots[] = [
                    'time' => $timeString,
                    'start_time' => $start->format('H:i:s'),
                    'end_time' => $slotEnd->format('H:i:s'),
                    'is_available' => !$isBooked,
                    'is_booked' => $isBooked,
                ];
            }

            $start->addMinutes($slotDuration);
        }

        // 4. محدود کردن به تعداد مجاز در روز
        if ($schedule->max_slots_per_day) {
            $availableSlots = array_filter($allSlots, fn($slot) => $slot['is_available']);
            if (count($availableSlots) > $schedule->max_slots_per_day) {
                $allSlots = array_slice($allSlots, 0, $schedule->max_slots_per_day);
            }
        }

        return [
            'available' => true,
            'date' => $date,
            'doctor' => [
                'id' => $doctor->id,
                'name' => $doctor->full_name,
                'specialty' => $doctor->specialty?->name,
                'consultation_fee' => $doctor->consultation_fee,
            ],
            'slots' => $allSlots,
            'total_slots' => count($allSlots),
            'available_slots' => count(array_filter($allSlots, fn($slot) => $slot['is_available'])),
        ];
    }

    /**
     * رزرو نوبت جدید
     */
    public function bookAppointment(array $data): Appointment
    {
        return DB::transaction(function () use ($data) {
            // 1. اعتبارسنجی زمان انتخابی
            $this->validateSlotAvailability(
                $data['doctor_id'],
                $data['date'],
                $data['start_time']
            );

            // 2. پیدا کردن یا ایجاد بیمار
            $patient = $this->getOrCreatePatient($data);

            // 3. دریافت اطلاعات پزشک و هزینه
            $doctor = Doctor::findOrFail($data['doctor_id']);
            $fee = $doctor->consultation_fee ?? 0;

            // 4. ایجاد نوبت
            $appointment = Appointment::create([
                'patient_id' => $patient->id,
                'doctor_id' => $data['doctor_id'],
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

            // 5. محاسبه زمان پایان
            $endTime = Carbon::parse($appointment->start_time)
                ->addMinutes($appointment->duration)
                ->format('H:i:s');
            $appointment->update(['end_time' => $endTime]);

            // 6. ارسال پیامک تایید (در پس‌زمینه)
            // $this->sendConfirmationSms($appointment);

            return $appointment->load(['patient.user', 'doctor.user', 'doctor.specialty']);
        });
    }

    /**
     * تایید نوبت (توسط پزشک)
     */
    public function confirmAppointment(Appointment $appointment): Appointment
    {
        if ($appointment->status !== Appointment::STATUS_PENDING) {
            throw new \Exception('فقط نوبت‌های در انتظار تایید قابل تایید هستند');
        }

        $appointment->update(['status' => Appointment::STATUS_CONFIRMED]);

        // ارسال پیامک به بیمار
        // $this->sendAppointmentConfirmedSms($appointment);

        return $appointment->fresh();
    }

    /**
     * لغو نوبت (توسط بیمار یا پزشک)
     */
    public function cancelAppointment(Appointment $appointment, string $reason = null): Appointment
    {
        if (!$appointment->canCancel()) {
            throw new \Exception('امکان لغو این نوبت وجود ندارد');
        }

        $appointment->update([
            'status' => Appointment::STATUS_CANCELLED,
            'notes' => $reason ? "لغو شده: {$reason}" : $appointment->notes,
        ]);

        // ارسال پیامک لغو
        // $this->sendCancellationSms($appointment);

        return $appointment->fresh();
    }

    /**
     * تغییر زمان نوبت
     */
    public function rescheduleAppointment(Appointment $appointment, array $data): Appointment
    {
        if (!$appointment->canReschedule()) {
            throw new \Exception('امکان تغییر زمان این نوبت وجود ندارد');
        }

        // اعتبارسنجی زمان جدید
        $this->validateSlotAvailability(
            $appointment->doctor_id,
            $data['date'],
            $data['start_time'],
            $appointment->id // استثنا برای نوبت فعلی
        );

        $appointment->update([
            'date' => $data['date'],
            'start_time' => $data['start_time'],
            'status' => Appointment::STATUS_PENDING,
        ]);

        // محاسبه مجدد زمان پایان
        $endTime = Carbon::parse($appointment->start_time)
            ->addMinutes($appointment->duration)
            ->format('H:i:s');
        $appointment->update(['end_time' => $endTime]);

        // ارسال پیامک
        // $this->sendRescheduleSms($appointment);

        return $appointment->fresh();
    }

    /**
     * شروع ویزیت (حضور بیمار)
     */
    public function startAppointment(Appointment $appointment): Appointment
    {
        if ($appointment->status !== Appointment::STATUS_CONFIRMED) {
            throw new \Exception('فقط نوبت‌های تایید شده قابل شروع هستند');
        }

        $appointment->update(['status' => Appointment::STATUS_ARRIVED]);

        return $appointment->fresh();
    }

    /**
     * پایان ویزیت
     */
    public function completeAppointment(Appointment $appointment): Appointment
    {
        if ($appointment->status !== Appointment::STATUS_ARRIVED) {
            throw new \Exception('فقط نوبت‌های حاضر قابل پایان هستند');
        }

        $appointment->update(['status' => Appointment::STATUS_COMPLETED]);

        return $appointment->fresh();
    }

    /**
     * بیمار حاضر نشده
     */
    public function markNoShow(Appointment $appointment): Appointment
    {
        if ($appointment->status === Appointment::STATUS_COMPLETED) {
            throw new \Exception('نوبت قبلاً انجام شده است');
        }

        $appointment->update(['status' => Appointment::STATUS_NO_SHOW]);

        return $appointment->fresh();
    }

    /**
     * دریافت تاریخچه نوبت‌های بیمار
     */
    public function patientAppointments(Patient $patient, array $filters = [], int $perPage = 15)
    {
        $query = Appointment::where('patient_id', $patient->id)
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

        return $query->orderBy('date', 'desc')->paginate($perPage);
    }

    /**
     * دریافت نوبت‌های پزشک
     */
    public function doctorAppointments(Doctor $doctor, array $filters = [], int $perPage = 15)
    {
        $query = Appointment::where('doctor_id', $doctor->id)
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

        return $query->orderBy('date', 'desc')->orderBy('start_time', 'desc')->paginate($perPage);
    }

    /**
     * اعتبارسنجی زمان انتخابی
     */
    protected function validateSlotAvailability(
        int $doctorId,
        string $date,
        string $startTime,
        ?int $excludeAppointmentId = null
    ): void {
        // 1. بررسی تداخل با نوبت‌های دیگر
        $query = Appointment::where('doctor_id', $doctorId)
            ->whereDate('date', $date)
            ->where('start_time', $startTime)
            ->whereIn('status', [
                Appointment::STATUS_PENDING,
                Appointment::STATUS_CONFIRMED,
                Appointment::STATUS_ARRIVED,
                Appointment::STATUS_IN_PROGRESS
            ]);

        if ($excludeAppointmentId) {
            $query->where('id', '!=', $excludeAppointmentId);
        }

        if ($query->exists()) {
            throw new \Exception('این زمان قبلاً توسط شخص دیگری رزرو شده است');
        }

        // 2. بررسی اینکه زمان در محدوده کاری پزشک باشد
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        $schedule = DoctorSchedule::where('doctor_id', $doctorId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->first();

        if (!$schedule) {
            throw new \Exception('پزشک در این روز کاری ندارد');
        }

        $start = Carbon::parse($startTime);
        $scheduleStart = Carbon::parse($schedule->start_time);
        $scheduleEnd = Carbon::parse($schedule->end_time);

        if ($start < $scheduleStart || $start >= $scheduleEnd) {
            throw new \Exception('زمان انتخابی خارج از ساعات کاری پزشک است');
        }

        // 3. بررسی زمان استراحت
        if ($schedule->break_start && $schedule->break_end) {
            $breakStart = Carbon::parse($schedule->break_start);
            $breakEnd = Carbon::parse($schedule->break_end);
            if ($start >= $breakStart && $start < $breakEnd) {
                throw new \Exception('زمان انتخابی در زمان استراحت پزشک است');
            }
        }
    }

    /**
     * پیدا کردن یا ایجاد بیمار
     */
    protected function getOrCreatePatient(array $data): Patient
    {
        // اگر patient_id داده شده
        if (isset($data['patient_id'])) {
            return Patient::findOrFail($data['patient_id']);
        }

        // اگر national_code داده شده
        if (isset($data['national_code'])) {
            $patient = Patient::where('national_code', $data['national_code'])->first();
            if ($patient) {
                return $patient;
            }
        }

        // ایجاد بیمار جدید
        $userData = [
            'name' => $data['patient_name'] ?? 'بیمار',
            'mobile' => $data['mobile'] ?? null,
            'is_active' => true,
        ];

        // اگر email داده شده
        if (isset($data['email'])) {
            $userData['email'] = $data['email'];
        }

        $user = \App\Models\User::create($userData);

        // اختصاص نقش بیمار
        $user->assignRole('patient');

        $patient = Patient::create([
            'user_id' => $user->id,
            'national_code' => $data['national_code'] ?? null,
            'phone' => $data['phone'] ?? $data['mobile'] ?? null,
            'is_active' => true,
            'verified_at' => now(),
        ]);

        return $patient;
    }

    /**
     * ارسال پیامک تایید (برای بعد)
     */
    protected function sendConfirmationSms(Appointment $appointment): void
    {
        try {
            $patient = $appointment->patient;
            if ($patient && $patient->phone) {
                $message = "نوبت شما با دکتر {$appointment->doctor->full_name} در تاریخ {$appointment->date->format('Y/m/d')} ساعت {$appointment->start_time->format('H:i')} ثبت شد.";
                // app(SmsManager::class)->send($patient->phone, $message);
            }
        } catch (\Exception $e) {
            Log::error('SMS sending failed: ' . $e->getMessage());
        }
    }
}
