<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ScheduleController extends Controller
{
    use ApiResponse;

    private const DAYS = [
        0 => 'saturday',
        1 => 'sunday',
        2 => 'monday',
        3 => 'tuesday',
        4 => 'wednesday',
        5 => 'thursday',
        6 => 'friday',
    ];

    private const DAY_NUMBERS = [
        'saturday' => 0,
        'sunday' => 1,
        'monday' => 2,
        'tuesday' => 3,
        'wednesday' => 4,
        'thursday' => 5,
        'friday' => 6,
    ];

    public function weekly(
        Request $request,
        int $doctorId
    ) {
        $this->findDoctor($request, $doctorId);

        $schedules = DoctorSchedule::query()
            ->where('doctor_id', $doctorId)
            ->where('is_special', false)
            ->orderBy('day_of_week')
            ->get()
            ->map(
                fn (DoctorSchedule $schedule) =>
                $this->serialize($schedule)
            )
            ->values();

        return $this->success($schedules);
    }

    public function setWeekly(
        Request $request,
        int $doctorId
    ) {
        $this->findDoctor($request, $doctorId);

        $validator = Validator::make(
            $request->all(),
            [
                'schedules' =>
                    'required|array|min:1|max:7',

                'schedules.*.day_of_week' =>
                    'required',

                'schedules.*.start_time' =>
                    'required|date_format:H:i',

                'schedules.*.end_time' =>
                    'required|date_format:H:i',

                'schedules.*.break_start' =>
                    'nullable|date_format:H:i',

                'schedules.*.break_end' =>
                    'nullable|date_format:H:i',

                'schedules.*.slot_duration' =>
                    'nullable|integer|min:5|max:240',

                'schedules.*.max_slots_per_day' =>
                    'nullable|integer|min:1|max:500',

                'schedules.*.is_working' =>
                    'nullable|boolean',

                'schedules.*.is_active' =>
                    'nullable|boolean',
            ]
        );

        if ($validator->fails()) {
            return $this->error(
                'خطا در اعتبارسنجی',
                422,
                $validator->errors()
            );
        }

        $normalized = collect(
            $request->input('schedules', [])
        )
            ->map(function (array $item) {
                $day = $this->normalizeDay(
                    $item['day_of_week'] ?? null
                );

                if ($day === null) {
                    return null;
                }

                return [
                    'day_of_week' => $day,

                    'start_time' =>
                        $item['start_time'],

                    'end_time' =>
                        $item['end_time'],

                    'break_start' =>
                        $item['break_start'] ?? null,

                    'break_end' =>
                        $item['break_end'] ?? null,

                    'slot_duration' => (int) (
                        $item['slot_duration'] ?? 30
                    ),

                    'max_slots_per_day' => (int) (
                        $item['max_slots_per_day'] ?? 20
                    ),

                    'is_active' => array_key_exists(
                        'is_working',
                        $item
                    )
                        ? (bool) $item['is_working']
                        : (bool) (
                            $item['is_active'] ?? true
                        ),
                ];
            })
            ->filter()
            ->unique('day_of_week')
            ->values();

        if ($normalized->isEmpty()) {
            return $this->error(
                'روزهای ساعات کاری نامعتبر هستند',
                422
            );
        }

        foreach ($normalized as $item) {
            if (
                $item['end_time'] <=
                $item['start_time']
            ) {
                return $this->error(
                    'ساعت پایان باید بعد از ساعت شروع باشد',
                    422
                );
            }

            if (
                $item['break_start'] &&
                $item['break_end'] &&
                $item['break_end'] <=
                $item['break_start']
            ) {
                return $this->error(
                    'پایان استراحت باید بعد از شروع استراحت باشد',
                    422
                );
            }
        }

        $records = DB::transaction(
            function () use (
                $normalized,
                $doctorId
            ) {
                $submittedDays = $normalized
                    ->pluck('day_of_week')
                    ->all();

                DoctorSchedule::query()
                    ->where('doctor_id', $doctorId)
                    ->where('is_special', false)
                    ->whereNotIn(
                        'day_of_week',
                        $submittedDays
                    )
                    ->delete();

                $saved = collect();

                foreach ($normalized as $item) {
                    $schedule =
                        DoctorSchedule::withTrashed()
                            ->where(
                                'doctor_id',
                                $doctorId
                            )
                            ->where(
                                'day_of_week',
                                $item['day_of_week']
                            )
                            ->where(
                                'is_special',
                                false
                            )
                            ->first();

                    if (!$schedule) {
                        $schedule =
                            new DoctorSchedule();
                    }

                    if ($schedule->trashed()) {
                        $schedule->restore();
                    }

                    $schedule->fill([
                        'doctor_id' =>
                            $doctorId,

                        'day_of_week' =>
                            $item['day_of_week'],

                        'start_time' =>
                            $item['start_time'],

                        'end_time' =>
                            $item['end_time'],

                        'break_start' =>
                            $item['break_start'],

                        'break_end' =>
                            $item['break_end'],

                        'slot_duration' =>
                            $item['slot_duration'],

                        'max_slots_per_day' =>
                            $item[
                            'max_slots_per_day'
                            ],

                        'is_active' =>
                            $item['is_active'],

                        'is_special' =>
                            false,

                        'special_date' =>
                            null,

                        'special_reason' =>
                            null,
                    ]);

                    $schedule->save();

                    $saved->push(
                        $schedule->fresh()
                    );
                }

                return $saved;
            }
        );

        return $this->success(
            $records
                ->map(
                    fn (
                        DoctorSchedule $schedule
                    ) => $this->serialize(
                        $schedule
                    )
                )
                ->values(),
            'ساعات کاری با موفقیت ذخیره شد'
        );
    }

    public function day(
        Request $request,
        int $doctorId
    ) {
        $this->findDoctor($request, $doctorId);

        $validator = Validator::make(
            $request->all(),
            [
                'date' => 'required|date',
            ]
        );

        if ($validator->fails()) {
            return $this->error(
                'تاریخ نامعتبر است',
                422,
                $validator->errors()
            );
        }

        $date = Carbon::parse(
            $request->input('date')
        );

        $special = DoctorSchedule::query()
            ->where('doctor_id', $doctorId)
            ->where('is_special', true)
            ->whereDate(
                'special_date',
                $date->toDateString()
            )
            ->first();

        if ($special) {
            return $this->success(
                $this->serialize($special)
            );
        }

        $dayOfWeek =
            ($date->dayOfWeek + 1) % 7;

        $schedule = DoctorSchedule::query()
            ->where('doctor_id', $doctorId)
            ->where('is_special', false)
            ->where(
                'day_of_week',
                $dayOfWeek
            )
            ->first();

        return $this->success(
            $schedule
                ? $this->serialize($schedule)
                : null
        );
    }

    public function special(
        Request $request,
        int $doctorId
    ) {
        $this->findDoctor($request, $doctorId);

        $items = DoctorSchedule::query()
            ->where('doctor_id', $doctorId)
            ->where('is_special', true)
            ->orderBy('special_date')
            ->get()
            ->map(
                fn (DoctorSchedule $schedule) =>
                $this->serialize($schedule)
            )
            ->values();

        return $this->success($items);
    }

    public function setSpecial(
        Request $request,
        int $doctorId
    ) {
        $this->findDoctor($request, $doctorId);

        $validator = Validator::make(
            $request->all(),
            [
                'special_date' =>
                    'required|date',

                'start_time' =>
                    'nullable|date_format:H:i',

                'end_time' =>
                    'nullable|date_format:H:i',

                'special_reason' =>
                    'nullable|string|max:255',

                'is_active' =>
                    'nullable|boolean',
            ]
        );

        if ($validator->fails()) {
            return $this->error(
                'خطا در اعتبارسنجی',
                422,
                $validator->errors()
            );
        }

        $date = Carbon::parse(
            $request->input('special_date')
        );

        $isActive = $request->boolean(
            'is_active',
            true
        );

        $startTime = $request->input(
            'start_time'
        );

        $endTime = $request->input(
            'end_time'
        );

        if (
            $isActive &&
            (!$startTime || !$endTime)
        ) {
            return $this->error(
                'ساعت شروع و پایان الزامی است',
                422
            );
        }

        if (
            $startTime &&
            $endTime &&
            $endTime <= $startTime
        ) {
            return $this->error(
                'ساعت پایان باید بعد از ساعت شروع باشد',
                422
            );
        }

        $schedule =
            DoctorSchedule::withTrashed()
                ->where(
                    'doctor_id',
                    $doctorId
                )
                ->where(
                    'is_special',
                    true
                )
                ->whereDate(
                    'special_date',
                    $date->toDateString()
                )
                ->first();

        if (!$schedule) {
            $schedule =
                new DoctorSchedule();
        }

        if ($schedule->trashed()) {
            $schedule->restore();
        }

        $schedule->fill([
            'doctor_id' =>
                $doctorId,

            'day_of_week' =>
                ($date->dayOfWeek + 1) % 7,

            'start_time' =>
                $startTime ?: '00:00',

            'end_time' =>
                $endTime ?: '00:00',

            'break_start' =>
                null,

            'break_end' =>
                null,

            'slot_duration' =>
                30,

            'max_slots_per_day' =>
                20,

            'is_active' =>
                $isActive,

            'is_special' =>
                true,

            'special_date' =>
                $date->toDateString(),

            'special_reason' =>
                $request->input(
                    'special_reason'
                ),
        ]);

        $schedule->save();

        return $this->success(
            $this->serialize(
                $schedule->fresh()
            ),
            'زمان ویژه با موفقیت ذخیره شد'
        );
    }

    public function deleteSpecial(
        Request $request,
        int $scheduleId
    ) {
        $schedule = DoctorSchedule::query()
            ->where('is_special', true)
            ->findOrFail($scheduleId);

        $this->findDoctor(
            $request,
            (int) $schedule->doctor_id
        );

        $schedule->delete();

        return $this->success(
            null,
            'زمان ویژه حذف شد'
        );
    }

    public function copyPreviousWeek(
        Request $request,
        int $doctorId
    ) {
        return $this->weekly(
            $request,
            $doctorId
        );
    }

    private function findDoctor(
        Request $request,
        int $doctorId
    ): Doctor {
        $query = Doctor::query()
            ->whereKey($doctorId);

        $tenantId =
            $request->user()?->tenant_id;

        if ($tenantId) {
            $query->where(
                'tenant_id',
                $tenantId
            );
        }

        return $query->firstOrFail();
    }

    private function normalizeDay(
        mixed $day
    ): ?int {
        if (is_numeric($day)) {
            $day = (int) $day;

            return $day >= 0 &&
            $day <= 6
                ? $day
                : null;
        }

        return self::DAY_NUMBERS[
        strtolower(
            trim((string) $day)
        )
        ] ?? null;
    }

    private function serialize(
        DoctorSchedule $schedule
    ): array {
        return [
            'id' =>
                $schedule->id,

            'doctor_id' =>
                $schedule->doctor_id,

            'day_of_week' =>
                self::DAYS[
                (int) $schedule->day_of_week
                ] ?? $schedule->day_of_week,

            'day_number' =>
                (int) $schedule->day_of_week,

            'start_time' =>
                $this->formatTime(
                    $schedule->start_time
                ),

            'end_time' =>
                $this->formatTime(
                    $schedule->end_time
                ),

            'break_start' =>
                $this->formatTime(
                    $schedule->break_start
                ),

            'break_end' =>
                $this->formatTime(
                    $schedule->break_end
                ),

            'slot_duration' =>
                (int) $schedule->slot_duration,

            'max_slots_per_day' =>
                (int) $schedule
                    ->max_slots_per_day,

            'is_working' =>
                (bool) $schedule->is_active,

            'is_active' =>
                (bool) $schedule->is_active,

            'is_special' =>
                (bool) $schedule->is_special,

            'special_date' =>
                $schedule->special_date
                    ? $schedule
                    ->special_date
                    ->format('Y-m-d')
                    : null,

            'special_reason' =>
                $schedule->special_reason,
        ];
    }

    private function formatTime(
        mixed $value
    ): ?string {
        if (!$value) {
            return null;
        }

        return substr(
            (string) $value,
            0,
            5
        );
    }
}
