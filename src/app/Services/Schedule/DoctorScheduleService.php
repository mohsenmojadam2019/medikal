<?php

namespace App\Services\Schedule;

use App\Models\DoctorSchedule;
use App\Models\Doctor;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DoctorScheduleService
{
    protected $tenantId;

    public function __construct()
    {
        $this->tenantId = session('tenant_id');
    }

    public function getWeeklySchedule(int $doctorId): array
    {
        $schedules = DoctorSchedule::where('tenant_id', $this->tenantId)
            ->where('doctor_id', $doctorId)
            ->where('is_special', false)
            ->where('is_active', true)
            ->orderBy('day_of_week')
            ->get();

        $result = [];
        for ($i = 0; $i < 7; $i++) {
            $result[$i] = $schedules->firstWhere('day_of_week', $i);
        }

        return $result;
    }

    public function getSpecialSchedules(int $doctorId): array
    {
        return DoctorSchedule::where('tenant_id', $this->tenantId)
            ->where('doctor_id', $doctorId)
            ->where('is_special', true)
            ->where('is_active', true)
            ->orderBy('special_date')
            ->get()
            ->toArray();
    }

    public function setWeeklySchedule(int $doctorId, array $schedules): array
    {
        return DB::transaction(function () use ($doctorId, $schedules) {
            $created = [];
            foreach ($schedules as $schedule) {
                if (empty($schedule['start_time']) || empty($schedule['end_time'])) {
                    continue;
                }

                $record = DoctorSchedule::updateOrCreate(
                    [
                        'tenant_id' => $this->tenantId,
                        'doctor_id' => $doctorId,
                        'day_of_week' => $schedule['day_of_week'],
                        'is_special' => false,
                    ],
                    [
                        'start_time' => $schedule['start_time'],
                        'end_time' => $schedule['end_time'],
                        'break_start' => $schedule['break_start'] ?? null,
                        'break_end' => $schedule['break_end'] ?? null,
                        'slot_duration' => $schedule['slot_duration'] ?? 30,
                        'max_slots_per_day' => $schedule['max_slots_per_day'] ?? 20,
                        'is_active' => $schedule['is_active'] ?? true,
                    ]
                );

                $created[] = $record;
            }

            return $created;
        });
    }

    public function setSpecialSchedule(int $doctorId, array $data): DoctorSchedule
    {
        return DB::transaction(function () use ($doctorId, $data) {
            $existing = DoctorSchedule::where('tenant_id', $this->tenantId)
                ->where('doctor_id', $doctorId)
                ->where('is_special', true)
                ->whereDate('special_date', $data['special_date'])
                ->first();

            if ($existing) {
                $existing->update([
                    'start_time' => $data['start_time'] ?? null,
                    'end_time' => $data['end_time'] ?? null,
                    'special_reason' => $data['special_reason'] ?? null,
                    'is_active' => $data['is_active'] ?? true,
                ]);
                return $existing->fresh();
            }

            return DoctorSchedule::create([
                'tenant_id' => $this->tenantId,
                'doctor_id' => $doctorId,
                'day_of_week' => Carbon::parse($data['special_date'])->dayOfWeek,
                'start_time' => $data['start_time'] ?? null,
                'end_time' => $data['end_time'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'is_special' => true,
                'special_date' => $data['special_date'],
                'special_reason' => $data['special_reason'] ?? null,
                'slot_duration' => 30,
                'max_slots_per_day' => 20,
            ]);
        });
    }

    public function deleteSpecialSchedule(int $scheduleId): void
    {
        $schedule = DoctorSchedule::where('tenant_id', $this->tenantId)
            ->where('is_special', true)
            ->findOrFail($scheduleId);

        $schedule->delete();
    }

    public function copyFromPreviousWeek(int $doctorId): array
    {
        $lastWeekSchedules = DoctorSchedule::where('tenant_id', $this->tenantId)
            ->where('doctor_id', $doctorId)
            ->where('is_special', false)
            ->where('is_active', true)
            ->get();

        if ($lastWeekSchedules->isEmpty()) {
            throw new \Exception('زمانبندی برای کپی وجود ندارد');
        }

        return DB::transaction(function () use ($doctorId, $lastWeekSchedules) {
            DoctorSchedule::where('tenant_id', $this->tenantId)
                ->where('doctor_id', $doctorId)
                ->where('is_special', false)
                ->delete();

            $created = [];
            foreach ($lastWeekSchedules as $schedule) {
                $created[] = DoctorSchedule::create([
                    'tenant_id' => $this->tenantId,
                    'doctor_id' => $doctorId,
                    'day_of_week' => $schedule->day_of_week,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'break_start' => $schedule->break_start,
                    'break_end' => $schedule->break_end,
                    'slot_duration' => $schedule->slot_duration,
                    'max_slots_per_day' => $schedule->max_slots_per_day,
                    'is_active' => $schedule->is_active,
                    'is_special' => false,
                ]);
            }

            return $created;
        });
    }

    public function getDoctorCalendar(int $doctorId, string $month, string $year): array
    {
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        $weeklySchedules = $this->getWeeklySchedule($doctorId);

        $specialSchedules = DoctorSchedule::where('tenant_id', $this->tenantId)
            ->where('doctor_id', $doctorId)
            ->where('is_special', true)
            ->whereBetween('special_date', [$startDate, $endDate])
            ->get()
            ->keyBy(function ($item) {
                return $item->special_date->format('Y-m-d');
            });

        $appointments = \App\Models\Appointment::where('tenant_id', $this->tenantId)
            ->where('doctor_id', $doctorId)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('status', ['pending', 'confirmed', 'arrived', 'in_progress'])
            ->get()
            ->groupBy('date');

        $calendar = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $dayOfWeek = $current->dayOfWeek;
            $dateKey = $current->format('Y-m-d');

            $schedule = null;
            $isSpecial = false;

            if ($specialSchedules->has($dateKey)) {
                $schedule = $specialSchedules->get($dateKey);
                $isSpecial = true;
            } else {
                $schedule = $weeklySchedules[$dayOfWeek] ?? null;
            }

            $calendar[] = [
                'date' => $dateKey,
                'day_name' => $current->format('l'),
                'is_weekend' => in_array($dayOfWeek, [5, 6]),
                'is_special' => $isSpecial,
                'special_reason' => $isSpecial ? $schedule?->special_reason : null,
                'has_schedule' => !empty($schedule),
                'schedule' => $schedule ? [
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'break_start' => $schedule->break_start,
                    'break_end' => $schedule->break_end,
                ] : null,
                'appointments_count' => $appointments->has($dateKey) ? $appointments->get($dateKey)->count() : 0,
            ];

            $current->addDay();
        }

        return $calendar;
    }

    public function getDaySchedule(int $doctorId, string $date): array
    {
        $carbonDate = Carbon::parse($date);
        $dayOfWeek = $carbonDate->dayOfWeek;

        $special = DoctorSchedule::where('tenant_id', $this->tenantId)
            ->where('doctor_id', $doctorId)
            ->where('is_special', true)
            ->whereDate('special_date', $date)
            ->first();

        if ($special) {
            return [
                'date' => $date,
                'is_special' => true,
                'special_reason' => $special->special_reason,
                'schedule' => $special,
                'slots' => $this->generateTimeSlots($special),
            ];
        }

        $schedule = DoctorSchedule::where('tenant_id', $this->tenantId)
            ->where('doctor_id', $doctorId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_special', false)
            ->where('is_active', true)
            ->first();

        if (!$schedule) {
            return [
                'date' => $date,
                'is_special' => false,
                'has_schedule' => false,
                'message' => 'پزشک در این روز کاری ندارد',
            ];
        }

        return [
            'date' => $date,
            'is_special' => false,
            'has_schedule' => true,
            'schedule' => $schedule,
            'slots' => $this->generateTimeSlots($schedule),
        ];
    }

    private function generateTimeSlots(DoctorSchedule $schedule): array
    {
        if (!$schedule->start_time || !$schedule->end_time) {
            return [];
        }

        $start = strtotime($schedule->start_time);
        $end = strtotime($schedule->end_time);
        $duration = $schedule->slot_duration ?? 30;
        $slots = [];

        while ($start < $end) {
            $slotEnd = $start + ($duration * 60);

            $isBreak = false;
            if ($schedule->break_start && $schedule->break_end) {
                $breakStart = strtotime($schedule->break_start);
                $breakEnd = strtotime($schedule->break_end);
                if ($start >= $breakStart && $start < $breakEnd) {
                    $isBreak = true;
                }
            }

            $slots[] = [
                'start_time' => date('H:i', $start),
                'end_time' => date('H:i', $slotEnd),
                'is_break' => $isBreak,
            ];

            $start = $slotEnd;
        }

        return $slots;
    }
}
