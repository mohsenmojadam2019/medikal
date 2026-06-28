<?php

namespace App\Services\Appointment;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\DoctorSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AppointmentService
{
    protected $tenantId;

    public function __construct()
    {
        $this->tenantId = session('tenant_id');
    }

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

        if ($schedule->max_slots_per_day) {
            $availableSlots = array_filter($allSlots, function($slot) {
                return $slot['is_available'];
            });
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
            'available_slots' => count(array_filter($allSlots, function($slot) {
                return $slot['is_available'];
            })),
        ];
    }

    public function bookAppointment(array $data): Appointment
    {
        return DB::transaction(function () use ($data) {
            $this->validateSlotAvailability(
                $data['doctor_id'],
                $data['date'],
                $data['start_time']
            );

            $patient = $this->getOrCreatePatient($data);
            $doctor = Doctor::findOrFail($data['doctor_id']);
            $fee = $doctor->consultation_fee ?? 0;

            $appointment = Appointment::create([
                'tenant_id' => $this->tenantId,
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

            $endTime = Carbon::parse($appointment->start_time)
                ->addMinutes($appointment->duration)
                ->format('H:i:s');
            $appointment->update(['end_time' => $endTime]);

            return $appointment->load(['patient.user', 'doctor.user', 'doctor.specialty']);
        });
    }

    public function confirmAppointment(Appointment $appointment): Appointment
    {
        if ($appointment->status !== Appointment::STATUS_PENDING) {
            throw new \Exception('فقط نوبت‌های در انتظار تایید قابل تایید هستند');
        }

        $appointment->update(['status' => Appointment::STATUS_CONFIRMED]);
        return $appointment->fresh();
    }

    public function cancelAppointment(Appointment $appointment, string $reason = null): Appointment
    {
        if ($appointment->canCancel() == false) {
            throw new \Exception('امکان لغو این نوبت وجود ندارد');
        }

        $appointment->update([
            'status' => Appointment::STATUS_CANCELLED,
            'notes' => $reason ? "لغو شده: {$reason}" : $appointment->notes,
        ]);

        return $appointment->fresh();
    }

    public function rescheduleAppointment(Appointment $appointment, array $data): Appointment
    {
        if ($appointment->canReschedule() == false) {
            throw new \Exception('امکان تغییر زمان این نوبت وجود ندارد');
        }

        $this->validateSlotAvailability(
            $appointment->doctor_id,
            $data['date'],
            $data['start_time'],
            $appointment->id
        );

        $appointment->update([
            'date' => $data['date'],
            'start_time' => $data['start_time'],
            'status' => Appointment::STATUS_PENDING,
        ]);

        $endTime = Carbon::parse($appointment->start_time)
            ->addMinutes($appointment->duration)
            ->format('H:i:s');
        $appointment->update(['end_time' => $endTime]);

        return $appointment->fresh();
    }

    public function startAppointment(Appointment $appointment): Appointment
    {
        if ($appointment->status !== Appointment::STATUS_CONFIRMED) {
            throw new \Exception('فقط نوبت‌های تایید شده قابل شروع هستند');
        }

        $appointment->update(['status' => Appointment::STATUS_ARRIVED]);
        return $appointment->fresh();
    }

    public function completeAppointment(Appointment $appointment): Appointment
    {
        if ($appointment->status !== Appointment::STATUS_ARRIVED) {
            throw new \Exception('فقط نوبت‌های حاضر قابل پایان هستند');
        }

        $appointment->update(['status' => Appointment::STATUS_COMPLETED]);
        return $appointment->fresh();
    }

    public function markNoShow(Appointment $appointment): Appointment
    {
        if ($appointment->status === Appointment::STATUS_COMPLETED) {
            throw new \Exception('نوبت قبلاً انجام شده است');
        }

        $appointment->update(['status' => Appointment::STATUS_NO_SHOW]);
        return $appointment->fresh();
    }

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

        return $query->orderBy('date', 'desc')->paginate($perPage);
    }

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

        return $query->orderBy('date', 'desc')->orderBy('start_time', 'desc')->paginate($perPage);
    }

    protected function validateSlotAvailability(
        int $doctorId,
        string $date,
        string $startTime,
        ?int $excludeAppointmentId = null
    ): void {
        $query = Appointment::where('doctor_id', $doctorId)
            ->where('tenant_id', $this->tenantId)
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

        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        $schedule = DoctorSchedule::where('doctor_id', $doctorId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->first();

        if (is_null($schedule)) {
            throw new \Exception('پزشک در این روز کاری ندارد');
        }

        $start = Carbon::parse($startTime);
        $scheduleStart = Carbon::parse($schedule->start_time);
        $scheduleEnd = Carbon::parse($schedule->end_time);

        if ($start < $scheduleStart || $start >= $scheduleEnd) {
            throw new \Exception('زمان انتخابی خارج از ساعات کاری پزشک است');
        }

        if ($schedule->break_start && $schedule->break_end) {
            $breakStart = Carbon::parse($schedule->break_start);
            $breakEnd = Carbon::parse($schedule->break_end);
            if ($start >= $breakStart && $start < $breakEnd) {
                throw new \Exception('زمان انتخابی در زمان استراحت پزشک است');
            }
        }
    }

    protected function getOrCreatePatient(array $data): Patient
    {
        if (isset($data['patient_id'])) {
            return Patient::where('tenant_id', $this->tenantId)->findOrFail($data['patient_id']);
        }

        if (isset($data['national_code'])) {
            $patient = Patient::where('tenant_id', $this->tenantId)
                ->where('national_code', $data['national_code'])
                ->first();
            if ($patient) {
                return $patient;
            }
        }

        $userData = [
            'name' => $data['patient_name'] ?? 'بیمار',
            'mobile' => $data['mobile'] ?? null,
            'is_active' => true,
        ];

        if (isset($data['email'])) {
            $userData['email'] = $data['email'];
        }

        $user = \App\Models\User::create($userData);
        $user->assignRole('patient');

        $patient = Patient::create([
            'tenant_id' => $this->tenantId,
            'user_id' => $user->id,
            'national_code' => $data['national_code'] ?? null,
            'phone' => $data['phone'] ?? $data['mobile'] ?? null,
            'is_active' => true,
            'verified_at' => now(),
        ]);

        return $patient;
    }
}
