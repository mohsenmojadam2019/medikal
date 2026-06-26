<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Schedule\DoctorScheduleService;
use App\Models\Doctor;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ScheduleController extends Controller
{
    use ApiResponse;

    protected DoctorScheduleService $scheduleService;

    public function __construct(DoctorScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }

    /**
     * دریافت زمانبندی هفتگی پزشک
     */
    public function weekly($doctorId)
    {
        try {
            $doctor = Doctor::findOrFail($doctorId);
            $schedules = $this->scheduleService->getWeeklySchedule($doctorId);
            return $this->success($schedules);
        } catch (\Exception $e) {
            return $this->error('پزشک یافت نشد', 404);
        }
    }

    /**
     * تنظیم زمانبندی هفتگی (پزشک)
     */
    public function setWeekly(Request $request, $doctorId)
    {
        $user = auth()->user();
        $doctor = Doctor::findOrFail($doctorId);

        // ✅ اصلاح: ادمین یا خود پزشک
        if (!$user->isAdmin() && $doctor->user_id != $user->id) {
            return $this->error('شما دسترسی به این بخش را ندارید', 403);
        }

        $validator = Validator::make($request->all(), [
            'schedules' => 'required|array|min:1|max:7',
            'schedules.*.day_of_week' => 'required|integer|min:0|max:6',
            'schedules.*.start_time' => 'nullable|date_format:H:i',
            'schedules.*.end_time' => 'nullable|date_format:H:i|after:schedules.*.start_time',
            'schedules.*.break_start' => 'nullable|date_format:H:i',
            'schedules.*.break_end' => 'nullable|date_format:H:i|after:schedules.*.break_start',
            'schedules.*.slot_duration' => 'nullable|integer|min:15|max:120',
            'schedules.*.max_slots_per_day' => 'nullable|integer|min:1',
            'schedules.*.is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $result = $this->scheduleService->setWeeklySchedule($doctorId, $request->schedules);
            return $this->success($result, 'زمانبندی هفتگی با موفقیت تنظیم شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تنظیم زمانبندی ویژه (تعطیلات/مرخصی)
     */
    public function setSpecial(Request $request, $doctorId)
    {
        $user = auth()->user();
        $doctor = Doctor::findOrFail($doctorId);

        if (!$user->isAdmin() && $doctor->user_id != $user->id) {
            return $this->error('شما دسترسی به این بخش را ندارید', 403);
        }

        $validator = Validator::make($request->all(), [
            'special_date' => 'required|date|after_or_equal:today',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'special_reason' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $result = $this->scheduleService->setSpecialSchedule($doctorId, $request->all());
            return $this->success($result, 'زمانبندی ویژه با موفقیت تنظیم شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * حذف زمانبندی ویژه
     */
    public function deleteSpecial($scheduleId)
    {
        try {
            $this->scheduleService->deleteSpecialSchedule($scheduleId);
            return $this->success(null, 'زمانبندی ویژه با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * کپی زمانبندی از هفته قبل
     */
    public function copyFromPreviousWeek(Request $request, $doctorId)
    {
        $user = auth()->user();
        $doctor = Doctor::findOrFail($doctorId);

        if (!$user->isAdmin() && $doctor->user_id != $user->id) {
            return $this->error('شما دسترسی به این بخش را ندارید', 403);
        }

        try {
            $result = $this->scheduleService->copyFromPreviousWeek($doctorId);
            return $this->success($result, 'زمانبندی با موفقیت از هفته قبل کپی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * دریافت تقویم نوبت‌های پزشک
     */
    public function calendar(Request $request, $doctorId)
    {
        $validator = Validator::make($request->all(), [
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2030',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $doctor = Doctor::findOrFail($doctorId);
            $calendar = $this->scheduleService->getDoctorCalendar(
                $doctorId,
                $request->month,
                $request->year
            );
            return $this->success($calendar);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * دریافت زمانبندی یک روز خاص
     */
    public function daySchedule(Request $request, $doctorId)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $doctor = Doctor::findOrFail($doctorId);
            $result = $this->scheduleService->getDaySchedule($doctorId, $request->date);
            return $this->success($result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * دریافت زمانبندی‌های ویژه (تعطیلات)
     */
    public function specialSchedules($doctorId)
    {
        try {
            $doctor = Doctor::findOrFail($doctorId);
            $schedules = $this->scheduleService->getSpecialSchedules($doctorId);
            return $this->success($schedules);
        } catch (\Exception $e) {
            return $this->error('پزشک یافت نشد', 404);
        }
    }
}
